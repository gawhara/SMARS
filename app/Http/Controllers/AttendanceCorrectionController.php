<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequestForm;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\AttendanceDailySummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceCorrectionController extends Controller
{
    public function index(Request $request): View
    {
        $corrections = AttendanceCorrectionRequest::query()
            ->with(['employee.company', 'attendanceRecord', 'requester', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('company_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('company_id', $request->integer('company_id'))))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('attendance.corrections.index', [
            'corrections' => $corrections,
            'companies' => Company::orderBy('name_en')->get(),
            'pendingCount' => AttendanceCorrectionRequest::where('status', 'pending')->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $record = $request->filled('record_id') ? AttendanceRecord::findOrFail($request->integer('record_id')) : null;

        return view('attendance.corrections.form', [
            'record' => $record,
            'employees' => Employee::orderBy('name_en')->get(['id', 'name_ar', 'name_en', 'employee_code']),
        ]);
    }

    public function store(AttendanceCorrectionRequestForm $request): RedirectResponse
    {
        $record = $request->filled('attendance_record_id')
            ? AttendanceRecord::findOrFail($request->integer('attendance_record_id'))
            : null;

        if ($record && $record->employee_id !== $request->integer('employee_id')) {
            return back()->withErrors(['attendance_record_id' => __('app.att.correction_employee_mismatch')])->withInput();
        }

        AttendanceCorrectionRequest::create($request->safe()->merge([
            'original_punch_at' => $record?->punch_at,
            'original_punch_type' => $record?->punch_type,
            'status' => 'pending',
            'requested_by' => $request->user()->id,
        ])->all());

        return redirect()->route('attendance.corrections.index')->with('status', __('app.att.correction_submitted'));
    }

    public function approve(Request $request, AttendanceCorrectionRequest $correction, AttendanceDailySummaryService $summaries): RedirectResponse
    {
        $validated = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);
        abort_unless($correction->status === 'pending', 409);

        if (app(\App\Services\Attendance\PayrollPeriodService::class)
            ->isLocked($correction->employee?->company_id, $correction->requested_punch_at)) {
            return back()->with('error', __('app.pay.period_locked'));
        }

        DB::transaction(function () use ($correction, $request, $validated, $summaries): void {
            $employee = $correction->employee;
            $original = $correction->attendanceRecord;
            $originalDate = $original?->punch_at?->copy();

            $replacement = AttendanceRecord::create([
                'employee_id' => $employee->id,
                'device_user_id' => $employee->hr_employee_id,
                'punch_at' => $correction->requested_punch_at,
                'punch_type' => $correction->requested_punch_type,
                'source' => 'correction',
                'company_id' => $employee->company_id,
                'branch_id' => $employee->branch_id,
                'notes' => __('app.att.correction_replacement_note', ['id' => $correction->id]),
            ]);

            $original?->delete();
            $correction->update([
                'replacement_record_id' => $replacement->id,
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'review_notes' => $validated['review_notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            if ($originalDate && ! $originalDate->isSameDay($replacement->punch_at)) {
                $summaries->rebuild($employee, $originalDate);
            }
            $summaries->rebuild($employee, $replacement->punch_at);
        });

        return back()->with('status', __('app.att.correction_approved_message'));
    }

    public function reject(Request $request, AttendanceCorrectionRequest $correction): RedirectResponse
    {
        $validated = $request->validate(['review_notes' => ['required', 'string', 'min:3', 'max:1000']]);
        abort_unless($correction->status === 'pending', 409);

        $correction->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'review_notes' => $validated['review_notes'],
            'reviewed_at' => now(),
        ]);

        return back()->with('status', __('app.att.correction_rejected_message'));
    }
}
