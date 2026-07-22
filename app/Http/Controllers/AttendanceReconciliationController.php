<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceDailySummary;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\AttendanceShiftPunchMatcher;
use App\Services\Attendance\PayrollPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceReconciliationController extends Controller
{
    public function __construct(
        private readonly AttendanceShiftPunchMatcher $matcher,
        private readonly PayrollPeriodService $periods,
    ) {
    }

    public function index(Request $request): View
    {
        $status = $request->input('status', 'open');
        $query = AttendanceDailySummary::query()
            ->with(['employee.company', 'employee.department', 'employee.shift', 'reconciler'])
            ->where('has_exception', true)
            ->when($status !== 'all', fn ($q) => $q->where('reconciliation_status', $status))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('company_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('company_id', $request->integer('company_id'))))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('attendance_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('attendance_date', '<=', $request->date('date_to')))
            ->when($request->filled('exception'), fn ($q) => $q->whereJsonContains('exception_codes', $request->input('exception')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = trim((string) $request->string('search'));
                $q->whereHas('employee', fn ($employee) => $employee
                    ->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('hr_employee_id', 'like', "%{$search}%"));
            });

        $summaries = $query->orderByDesc('attendance_date')->orderBy('employee_id')->paginate(25)->withQueryString();
        $rows = $this->hydrateRows($summaries->getCollection());
        $summaries->setCollection($rows);

        return view('attendance.reconciliation.index', [
            'summaries' => $summaries,
            'stats' => [
                'open' => AttendanceDailySummary::where('has_exception', true)->where('reconciliation_status', 'open')->count(),
                'approved' => AttendanceDailySummary::where('has_exception', true)->where('reconciliation_status', 'approved')->count(),
                'approved_today' => AttendanceDailySummary::where('reconciliation_status', 'approved')->whereDate('reconciled_at', today())->count(),
                'corrections' => AttendanceCorrectionRequest::where('status', 'pending')->count(),
            ],
            'companies' => Company::orderBy('name_en')->get(),
            'employees' => Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'hr_employee_id']),
            'exceptionTypes' => ['missing_in', 'missing_out', 'missing_period', 'repeated_in', 'repeated_out', 'unknown_type'],
            'status' => $status,
        ]);
    }

    public function approve(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'summary_ids' => ['required', 'array', 'min:1', 'max:100'],
            'summary_ids.*' => ['integer', 'distinct', 'exists:attendance_daily_summaries,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $summaries = AttendanceDailySummary::with('employee')->whereIn('id', $validated['summary_ids'])->get();
        $approved = 0;
        $locked = 0;
        $pending = 0;

        DB::transaction(function () use ($summaries, $request, $validated, &$approved, &$locked, &$pending): void {
            foreach ($summaries as $summary) {
                if (! $summary->has_exception || $summary->reconciliation_status === 'approved') {
                    continue;
                }
                if ($this->periods->isLocked($summary->employee?->company_id, $summary->attendance_date)) {
                    $locked++;
                    continue;
                }
                if ($this->hasPendingCorrection($summary)) {
                    $pending++;
                    continue;
                }

                $summary->update([
                    'reconciliation_status' => 'approved',
                    'reconciled_by' => $request->user()->id,
                    'reconciled_at' => now(),
                    'reconciliation_notes' => $validated['notes'] ?? null,
                ]);
                $approved++;
            }
        });

        return back()->with('status', __('app.recon.approved_result', [
            'approved' => $approved,
            'locked' => $locked,
            'pending' => $pending,
        ]));
    }

    public function reopen(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'summary_ids' => ['required', 'array', 'min:1', 'max:100'],
            'summary_ids.*' => ['integer', 'distinct', 'exists:attendance_daily_summaries,id'],
        ]);

        $summaries = AttendanceDailySummary::with('employee')
            ->whereIn('id', $validated['summary_ids'])
            ->where('has_exception', true)
            ->get();
        $count = 0;
        $locked = 0;

        DB::transaction(function () use ($summaries, &$count, &$locked): void {
            foreach ($summaries as $summary) {
                if ($this->periods->isLocked($summary->employee?->company_id, $summary->attendance_date)) {
                    $locked++;
                    continue;
                }

                $summary->update([
                    'reconciliation_status' => 'open',
                    'reconciled_by' => null,
                    'reconciled_at' => null,
                    'reconciliation_notes' => null,
                ]);
                $count++;
            }
        });

        return back()->with('status', __('app.recon.reopened_result', ['count' => $count, 'locked' => $locked]));
    }

    private function hydrateRows(Collection $summaries): Collection
    {
        if ($summaries->isEmpty()) {
            return collect();
        }

        $employeeIds = $summaries->pluck('employee_id')->unique();
        $dates = $summaries->pluck('attendance_date');
        $punches = AttendanceRecord::with('machine')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('punch_at', '>=', $dates->min())
            ->whereDate('punch_at', '<=', $dates->max())
            ->orderBy('punch_at')
            ->get()
            ->groupBy(fn (AttendanceRecord $record) => $record->employee_id.'|'.$record->punch_at->toDateString());

        $pendingCorrections = AttendanceCorrectionRequest::where('status', 'pending')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('requested_punch_at', '>=', $dates->min())
            ->whereDate('requested_punch_at', '<=', $dates->max())
            ->get()
            ->groupBy(fn ($correction) => $correction->employee_id.'|'.$correction->requested_punch_at->toDateString());

        return $summaries->map(function (AttendanceDailySummary $summary) use ($punches, $pendingCorrections): array {
            $key = $summary->employee_id.'|'.$summary->attendance_date->toDateString();
            $dayPunches = $punches->get($key, collect());

            return [
                'summary' => $summary,
                'punches' => $dayPunches,
                'periods' => $this->matcher->match($summary->employee, $summary->attendance_date, $dayPunches),
                'pending_correction' => $pendingCorrections->has($key),
                'locked' => $this->periods->isLocked($summary->employee?->company_id, $summary->attendance_date),
            ];
        });
    }

    private function hasPendingCorrection(AttendanceDailySummary $summary): bool
    {
        return AttendanceCorrectionRequest::where('employee_id', $summary->employee_id)
            ->where('status', 'pending')
            ->whereDate('requested_punch_at', $summary->attendance_date)
            ->exists();
    }
}
