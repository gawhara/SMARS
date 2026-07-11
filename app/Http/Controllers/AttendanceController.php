<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRecordRequest;
use App\Models\AttendanceMachine;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
