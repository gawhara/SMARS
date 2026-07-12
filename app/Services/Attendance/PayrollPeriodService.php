<?php

namespace App\Services\Attendance;

use App\Models\AttendanceDailySummary;
use App\Models\AttendanceHoliday;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Models\PayrollPeriod;
use Carbon\Carbon;

class PayrollPeriodService
{
    public function __construct(private readonly AttendanceReportService $report)
    {
    }

    /**
     * Is the month containing $date locked for $companyId?
     */
    public function isLocked(?int $companyId, Carbon|string $date): bool
    {
        if (! $companyId) {
            return false;
        }

        $month = ($date instanceof Carbon ? $date->copy() : Carbon::parse($date))->startOfMonth();

        return PayrollPeriod::where('company_id', $companyId)
            ->whereDate('period_month', $month)
            ->where('status', 'locked')
            ->exists();
    }

    /**
     * Per-employee payroll rows for a company + month: day-status counts, worked
     * hours (paired sessions), and overtime / late from the daily summaries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(Company $company, Carbon $month): array
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $employees = Employee::query()
            ->with(['shift', 'department'])
            ->where('company_id', $company->id)
            ->orderBy('name_en')
            ->get();

        $records = AttendanceRecord::query()
            ->matched()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('punch_at', [$from, $to])
            ->get(['employee_id', 'punch_at', 'punch_type']);

        $holidays = AttendanceHoliday::where('is_active', true)
            ->whereBetween('holiday_date', [$from, $to])->get();

        $leaves = EmployeeLeaveRequest::where('status', 'approved')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->get();

        $reportRows = collect($this->report->build($from, $to, $employees, $records, $holidays, $leaves))
            ->keyBy(fn ($row) => $row['employee']->id);

        // Overtime & late minutes aggregated from the computed daily summaries.
        $metrics = AttendanceDailySummary::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('attendance_date', [$from, $to])
            ->selectRaw('employee_id, SUM(overtime_minutes) ot, SUM(late_minutes) late')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        return $employees->map(function (Employee $employee) use ($reportRows, $metrics, $company) {
            $row = $reportRows[$employee->id] ?? [];
            $m = $metrics[$employee->id] ?? null;

            return [
                'employee' => $employee,
                'company' => $company,
                'present' => $row['present'] ?? 0,
                'late' => $row['late'] ?? 0,
                'absent' => $row['absent'] ?? 0,
                'leave' => $row['leave'] ?? 0,
                'holiday' => $row['holiday'] ?? 0,
                'rest' => $row['rest'] ?? 0,
                'worked_days' => $row['worked_days'] ?? 0,
                'worked_hours' => $row['hours'] ?? 0,
                'overtime_hours' => round(((int) ($m->ot ?? 0)) / 60, 1),
                'late_minutes' => (int) ($m->late ?? 0),
                'basic_salary' => $employee->basic_salary,
            ];
        })->all();
    }

    /**
     * Ensure a period row exists (open) for the company + month.
     */
    public function ensurePeriod(int $companyId, Carbon $month): PayrollPeriod
    {
        return PayrollPeriod::firstOrCreate(
            ['company_id' => $companyId, 'period_month' => $month->copy()->startOfMonth()->toDateString()],
            ['status' => 'open'],
        );
    }
}
