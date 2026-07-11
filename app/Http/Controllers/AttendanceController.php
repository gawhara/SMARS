<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRecordRequest;
use App\Models\AttendanceMachine;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Services\Attendance\AttendanceMatrixService;
use App\Services\Attendance\AttendanceReportService;
use App\Services\Attendance\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $service)
    {
    }

    public function index(Request $request): View
    {
        $records = AttendanceRecord::query()
            ->with(['employee', 'machine', 'company'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('machine_id'), fn ($q) => $q->where('attendance_machine_id', $request->integer('machine_id')))
            ->when($request->input('match') === 'matched', fn ($q) => $q->matched())
            ->when($request->input('match') === 'unmatched', fn ($q) => $q->unmatched())
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('punch_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('punch_at', '<=', $request->date('date_to')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = trim((string) $request->string('search'));
                $q->where(function ($q) use ($search): void {
                    $q->where('device_user_id', 'like', "%{$search}%")
                        ->orWhereHas('employee', fn ($e) => $e->where('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('punch_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => AttendanceRecord::count(),
            'matched' => AttendanceRecord::matched()->count(),
            'unmatched' => AttendanceRecord::unmatched()->count(),
            'today' => AttendanceRecord::whereDate('punch_at', today())->count(),
        ];

        return view('attendance.index', [
            'records' => $records,
            'stats' => $stats,
            'companies' => Company::orderBy('name_en')->get(),
            'machines' => AttendanceMachine::orderBy('device_name')->get(),
        ]);
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
            ->get(['employee_id', 'punch_at']);

        return view('attendance.matrix', [
            'month' => $month,
            'daysInMonth' => $month->daysInMonth,
            'employees' => $employees,
            'matrix' => $matrixService->build($month, $employees, $records),
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
            ->get(['employee_id', 'punch_at']);

        $rows = $reportService->build($from, $to, $employees, $records);

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

        AttendanceRecord::create($request->safe()->merge([
            'device_user_id' => $employee->employee_code,
            'source' => 'manual',
            'company_id' => $employee->company_id,
            'branch_id' => $employee->branch_id,
        ])->all());

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
        $attendance->delete();

        return redirect()->route('attendance.index')->with('status', __('app.deleted_successfully'));
    }
}
