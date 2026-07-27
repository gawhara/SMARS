<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRecordRequest;
use App\Models\AttendanceMachine;
use App\Models\AttendanceDailySummary;
use App\Models\AttendanceHoliday;
use App\Models\EmployeeLeaveRequest;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Attendance\AttendanceMatrixService;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceDailySummaryService;
use App\Services\Attendance\AttendanceReportService;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\AttendanceShiftPunchMatcher;
use App\Services\Attendance\PayrollPeriodService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $service,
        private readonly AttendanceDailySummaryService $dailySummaryService,
        private readonly AttendanceShiftPunchMatcher $shiftPunchMatcher,
        private readonly PayrollPeriodService $periods,
    )
    {
    }

    /**
     * Punch log = an employee directory. Each row opens that employee's own
     * attendance page (all punches + an absence/leave dashboard).
     */
    public function index(Request $request): View|StreamedResponse
    {
        if ($request->input('export') === 'csv') {
            return $this->exportPunchesCsv($this->punchQuery($request)->with(['employee', 'machine'])->get());
        }

        $employees = Employee::query()
            ->with(['company', 'department'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = trim((string) $request->string('search'));
                $q->where(fn ($names) => $names
                    ->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('hr_employee_id', 'like', "%{$search}%"));
            })
            ->orderBy('name_en')
            ->paginate(24)
            ->withQueryString();

        return view('attendance.index', [
            'employees' => $employees,
            'companies' => Company::orderBy('name_en')->get(),
            'stats' => [
                'employees' => Employee::count(),
                'total' => AttendanceRecord::count(),
                'today' => AttendanceRecord::whereDate('punch_at', today())->count(),
                'unmatched' => AttendanceRecord::unmatched()->count(),
            ],
        ]);
    }

    /**
     * A single employee's attendance: an absence/leave dashboard for the selected
     * period plus the day-by-day punch record, both driven by the date filter.
     */
    public function employee(Request $request, Employee $employee, AttendanceReportService $reportService): View
    {
        $employee->loadMissing(['company', 'department', 'shift']);

        $defaultFrom = $employee->start_date ? Carbon::parse($employee->start_date) : Carbon::now()->startOfMonth();
        $from = $this->resolveDate($request->input('date_from'), $defaultFrom);
        $to = $this->resolveDate($request->input('date_to'), Carbon::now());

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $records = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('punch_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get(['employee_id', 'punch_at', 'punch_type']);

        $holidays = AttendanceHoliday::where('is_active', true)->whereBetween('holiday_date', [$from, $to])->get();
        $leaves = EmployeeLeaveRequest::where('status', 'approved')
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->orderByDesc('start_date')
            ->get();

        $summary = $reportService->build($from, $to, collect([$employee]), $records, $holidays, $leaves)[0];

        $days = AttendanceDailySummary::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$from, $to])
            ->orderByDesc('attendance_date')
            ->paginate(31)
            ->withQueryString();

        return view('attendance.employee', [
            'employee' => $employee,
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'days' => $days,
            'leaves' => $leaves,
        ]);
    }

    /**
     * Printable monthly attendance sheet for one employee: every calendar day of
     * the month with its status and in/out times, plus a summary.
     */
    public function printReport(Request $request, Employee $employee, AttendanceMatrixService $matrix, AttendancePolicyService $policyService): View
    {
        $employee->loadMissing(['company', 'department', 'shift']);

        // Any from–to pay period (falls back to a calendar month, then this month).
        [$from, $to] = $this->resolveReportRange($request);

        $records = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('punch_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get(['employee_id', 'punch_at', 'punch_type']);

        $holidays = AttendanceHoliday::where('is_active', true)->whereBetween('holiday_date', [$from, $to])->get();
        $leaves = EmployeeLeaveRequest::where('status', 'approved')
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->get();

        $punchesByDay = $records->groupBy(fn (AttendanceRecord $r) => $r->punch_at->toDateString());
        $daily = AttendanceDailySummary::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$from, $to])
            ->get()
            ->keyBy(fn ($s) => $s->attendance_date->toDateString());

        $policy = $policyService->forEmployee($employee);
        $shiftStart = $matrix->shiftStart($employee);
        $today = Carbon::today();

        // Iterate the period day by day (works across month boundaries, e.g. 22→22).
        $counts = ['present' => 0, 'late' => 0, 'absent' => 0, 'rest' => 0, 'holiday' => 0, 'leave' => 0];
        $rows = [];
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $dayPunches = $punchesByDay->get($date->toDateString(), collect());
            $status = $matrix->dayStatus(
                $date, $today, $dayPunches, $shiftStart,
                $policy->grace_minutes, $policy->weekend_days ?? [5],
                $matrix->calendarStatus($employee, $date, $holidays, $leaves),
            );

            if (isset($counts[$status])) {
                $counts[$status]++;
            }

            $rows[] = [
                'date' => $date->copy(),
                'status' => $status,
                'summary' => $daily->get($date->toDateString()),
            ];
        }

        $expected = $counts['present'] + $counts['late'] + $counts['absent'];

        return view('attendance.employee-print', [
            'employee' => $employee,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'summary' => $counts,
            'workedHours' => round((int) $daily->sum('worked_minutes') / 60, 1),
            'rate' => $expected > 0 ? (int) round(($counts['present'] + $counts['late']) / $expected * 100) : 100,
        ]);
    }

    /**
     * Resolve the report period from date_from/date_to, falling back to a month,
     * then the current month.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveReportRange(Request $request): array
    {
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $from = $this->resolveDate($request->input('date_from'), Carbon::now()->startOfMonth());
            $to = $this->resolveDate($request->input('date_to'), Carbon::now()->endOfMonth());
        } else {
            $month = $this->resolveMonth($request->input('month'));
            $from = $month->copy()->startOfMonth();
            $to = $month->copy()->endOfMonth();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * Punch log query with all filters (employee, device, date range, time-of-day, match, search).
     */
    private function punchQuery(Request $request)
    {
        return AttendanceRecord::query()
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('machine_id'), fn ($q) => $q->where('attendance_machine_id', $request->integer('machine_id')))
            ->when($request->input('match') === 'matched', fn ($q) => $q->matched())
            ->when($request->input('match') === 'unmatched', fn ($q) => $q->unmatched())
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('punch_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('punch_at', '<=', $request->date('date_to')))
            ->when($request->filled('time_from'), fn ($q) => $q->whereTime('punch_at', '>=', $request->input('time_from')))
            ->when($request->filled('time_to'), fn ($q) => $q->whereTime('punch_at', '<=', $request->input('time_to')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = trim((string) $request->string('search'));
                $q->where(function ($q) use ($search): void {
                    $q->where('device_user_id', 'like', "%{$search}%")
                        ->orWhereHas('employee', fn ($e) => $e->where('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%")
                            ->orWhere('hr_employee_id', 'like', "%{$search}%"));
                });
            });
    }

    private function exportPunchesCsv($records): StreamedResponse
    {
        return response()->streamDownload(function () use ($records): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Employee', 'Code', 'Device user ID', 'Punch at', 'Type', 'Device', 'Verification', 'Source']);

            foreach ($records->sortBy('punch_at') as $record) {
                fputcsv($out, [
                    $record->employee?->name_en,
                    $record->employee?->employee_code,
                    $record->device_user_id,
                    $record->punch_at->format('Y-m-d H:i:s'),
                    $record->punch_type,
                    $record->machine?->device_name,
                    $record->verification_type,
                    $record->source,
                ]);
            }

            fclose($out);
        }, 'punches_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function matrix(Request $request, AttendanceMatrixService $matrixService): View
    {
        $month = $this->resolveMonth($request->input('month'));

        $employees = Employee::query()
            ->with('shift')
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->orderBy('name_en')
            ->get();

        $records = AttendanceRecord::query()
            ->matched()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('punch_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get(['employee_id', 'punch_at', 'punch_type']);

        $holidays = AttendanceHoliday::where('is_active', true)->whereBetween('holiday_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->get();
        $leaves = EmployeeLeaveRequest::where('status', 'approved')->whereIn('employee_id', $employees->pluck('id'))->whereDate('start_date', '<=', $month->copy()->endOfMonth())->whereDate('end_date', '>=', $month->copy()->startOfMonth())->get();

        return view('attendance.matrix', [
            'month' => $month,
            'daysInMonth' => $month->daysInMonth,
            'employees' => $employees,
            'matrix' => $matrixService->build($month, $employees, $records, $holidays, $leaves),
            'companies' => Company::orderBy('name_en')->get(),
            'branches' => Branch::orderBy('name_en')->get(),
            'departments' => Department::orderBy('name_en')->get(),
        ]);
    }

    public function report(Request $request, AttendanceReportService $reportService): View|StreamedResponse
    {
        $from = $this->resolveDate($request->input('date_from'), Carbon::now()->startOfMonth());
        $to = $this->resolveDate($request->input('date_to'), Carbon::now());

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $employees = Employee::query()
            ->with(['shift', 'company', 'department'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->orderBy('name_en')
            ->get();

        $records = AttendanceRecord::query()
            ->matched()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('punch_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get(['employee_id', 'punch_at', 'punch_type']);

        $holidays = AttendanceHoliday::where('is_active', true)->whereBetween('holiday_date', [$from, $to])->get();
        $leaves = EmployeeLeaveRequest::where('status', 'approved')->whereIn('employee_id', $employees->pluck('id'))->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)->get();

        $rows = $reportService->build($from, $to, $employees, $records, $holidays, $leaves);

        if ($request->input('export') === 'csv') {
            return $this->exportCsv($rows, $from, $to);
        }

        return view('attendance.report', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'totals' => [
                'present' => array_sum(array_column($rows, 'present')),
                'late' => array_sum(array_column($rows, 'late')),
                'absent' => array_sum(array_column($rows, 'absent')),
                'hours' => round(array_sum(array_column($rows, 'hours')), 1),
            ],
            'companies' => Company::orderBy('name_en')->get(),
            'branches' => Branch::orderBy('name_en')->get(),
            'departments' => Department::orderBy('name_en')->get(),
        ]);
    }

    public function exceptions(Request $request): View
    {
        $query = AttendanceDailySummary::query()
            ->with(['employee.company', 'employee.department'])
            ->where('has_exception', true)
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('company_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('company_id', $request->integer('company_id'))))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('attendance_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('attendance_date', '<=', $request->date('date_to')))
            ->when($request->filled('exception'), fn ($q) => $q->whereJsonContains('exception_codes', $request->input('exception')));

        $summaries = $query->orderByDesc('attendance_date')->paginate(20)->withQueryString();

        return view('attendance.exceptions', [
            'summaries' => $summaries,
            'stats' => [
                'total' => AttendanceDailySummary::where('has_exception', true)->count(),
                'missing_in' => AttendanceDailySummary::whereJsonContains('exception_codes', 'missing_in')->count(),
                'missing_out' => AttendanceDailySummary::whereJsonContains('exception_codes', 'missing_out')->count(),
                'today' => AttendanceDailySummary::where('has_exception', true)->whereDate('attendance_date', today())->count(),
            ],
            'companies' => Company::orderBy('name_en')->get(),
            'employees' => Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code']),
            'exceptionTypes' => ['missing_in', 'missing_out', 'missing_period', 'repeated_in', 'repeated_out', 'unknown_type'],
        ]);
    }

    public function daily(Request $request): View
    {
        $summaries = AttendanceDailySummary::query()
            ->with(['employee.company', 'employee.department'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('company_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('company_id', $request->integer('company_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('attendance_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('attendance_date', '<=', $request->date('date_to')))
            ->orderByDesc('attendance_date')
            ->paginate(20)
            ->withQueryString();

        return view('attendance.daily', [
            'summaries' => $summaries,
            'companies' => Company::orderBy('name_en')->get(),
            'employees' => Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code']),
        ]);
    }

    private function exportCsv(array $rows, Carbon $from, Carbon $to): StreamedResponse
    {
        $filename = 'attendance_'.$from->format('Ymd').'_'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel/Arabic
            fputcsv($out, ['Employee', 'Code', 'Company', 'Department', 'Present', 'Late', 'Absent', 'Rest', 'Worked days', 'Punches', 'Hours']);

            foreach ($rows as $row) {
                $employee = $row['employee'];
                fputcsv($out, [
                    $employee->name_en,
                    $employee->employee_code,
                    $employee->company?->name_en,
                    $employee->department?->name_en,
                    $row['present'], $row['late'], $row['absent'], $row['rest'],
                    $row['worked_days'], $row['punches'], $row['hours'],
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function resolveDate(?string $value, Carbon $default): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : $default->copy();
        } catch (\Throwable) {
            return $default->copy();
        }
    }

    private function resolveMonth(?string $value): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m', $value)->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Throwable) {
            return Carbon::now()->startOfMonth();
        }
    }

    public function create(): View
    {
        return view('attendance.create', [
            'record' => new AttendanceRecord(['punch_type' => 'in', 'punch_at' => now()->format('Y-m-d\TH:i')]),
            'employees' => Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code', 'company_id', 'branch_id']),
            'machines' => AttendanceMachine::where('is_active', true)->orderBy('device_name')->get(),
        ]);
    }

    public function store(AttendanceRecordRequest $request): RedirectResponse
    {
        $employee = Employee::findOrFail($request->integer('employee_id'));

        if ($this->periods->isLocked($employee->company_id, $request->input('punch_at'))) {
            return back()->with('error', __('app.pay.period_locked'))->withInput();
        }

        $record = AttendanceRecord::create($request->safe()->merge([
            'device_user_id' => $employee->hr_employee_id,
            'source' => 'manual',
            'company_id' => $employee->company_id,
            'branch_id' => $employee->branch_id,
        ])->all());

        $this->dailySummaryService->rebuild($employee, $record->punch_at);

        return redirect()->route('attendance.index')->with('status', __('app.saved_successfully'));
    }

    public function importForm(): View
    {
        return view('attendance.import', [
            'machines' => AttendanceMachine::where('is_active', true)->orderBy('device_name')->get(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'attendance_machine_id' => ['nullable', 'integer', 'exists:attendance_machines,id'],
        ]);

        $machine = $validated['attendance_machine_id'] ?? null
            ? AttendanceMachine::find($validated['attendance_machine_id'])
            : null;

        $batch = $this->service->importCsv($request->file('file'), $machine, (int) $request->user()->id);
        $this->dailySummaryService->rebuildForRecords(
            AttendanceRecord::where('sync_batch_id', $batch->id)->matched()->get(),
        );

        return redirect()->route('attendance.index', ['match' => $batch->unmatched_count > 0 ? 'unmatched' : null])
            ->with('status', __('app.attendance.import_summary', [
                'imported' => $batch->imported_count,
                'matched' => $batch->matched_count,
                'unmatched' => $batch->unmatched_count,
                'duplicate' => $batch->duplicate_count,
            ]));
    }

    public function destroy(AttendanceRecord $attendance): RedirectResponse
    {
        if ($this->periods->isLocked($attendance->company_id, $attendance->punch_at)) {
            return back()->with('error', __('app.pay.period_locked'));
        }

        $employee = $attendance->employee;
        $date = $attendance->punch_at->copy();
        $attendance->delete();

        if ($employee) {
            $this->dailySummaryService->rebuild($employee, $date);
        }

        return redirect()->route('attendance.index')->with('status', __('app.deleted_successfully'));
    }
}
