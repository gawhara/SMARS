<?php

namespace App\Services\Payroll;

use App\Models\AttendanceDailySummary;
use App\Models\AttendanceHoliday;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Services\Attendance\PayrollDeductionService;
use Carbon\Carbon;

/**
 * Overtime pay calculator (Labor Law art. 107): the hourly wage plus 50%.
 *
 * A baseline of overtime hours is pulled from the daily summaries (worked time
 * beyond the shift), but the caller may override the hours and the multiplier
 * so payroll can adjust manually before paying.
 */
class OvertimeService
{
    public function __construct(private readonly PayrollDeductionService $money)
    {
    }

    public function defaultMultiplier(): float
    {
        return (float) config('payroll.overtime.rate_multiplier', 1.5);
    }

    /**
     * @return array<string, mixed>
     */
    public function forEmployee(
        Employee $employee,
        Carbon $from,
        Carbon $to,
        ?float $hoursOverride = null,
        ?float $multiplier = null,
    ): array {
        $employee->loadMissing('shift');
        $multiplier ??= $this->defaultMultiplier();

        $holidays = AttendanceHoliday::where('is_active', true)
            ->whereBetween('holiday_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->get();
        $leaves = EmployeeLeaveRequest::where('status', 'approved')
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)->get();

        $salaryBasis = $this->money->salaryBasis($employee);
        // Hourly wage is monthly salary ÷ a single month's scheduled hours — a
        // stable per-hour rate that must not depend on how long the range is.
        $monthFrom = $from->copy()->startOfMonth();
        $monthTo = $from->copy()->endOfMonth();
        $monthlyScheduled = $this->money->scheduledMinutes($employee, $monthFrom, $monthTo, $holidays, $leaves);
        $hourlyRate = $this->money->hourlyRate($salaryBasis, $monthlyScheduled);

        $summaries = AttendanceDailySummary::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('overtime_minutes', '>', 0)
            ->orderBy('attendance_date')
            ->get(['attendance_date', 'overtime_minutes', 'worked_minutes']);

        $baselineMinutes = (int) $summaries->sum('overtime_minutes');
        $baselineHours = round($baselineMinutes / 60, 2);

        $adjusted = $hoursOverride !== null;
        $hours = $adjusted ? max(0.0, round($hoursOverride, 2)) : $baselineHours;

        $overtimeHourRate = round($hourlyRate * $multiplier, 4);
        $pay = round($hours * $overtimeHourRate, 2);

        $days = $summaries->map(fn ($s) => [
            'date' => $s->attendance_date,
            'overtime_hours' => round($s->overtime_minutes / 60, 2),
            'amount' => round(($s->overtime_minutes / 60) * $overtimeHourRate, 2),
        ])->all();

        return [
            'employee' => $employee,
            'from' => $from->copy(),
            'to' => $to->copy(),
            'salary_basis' => round($salaryBasis, 2),
            'hourly_rate' => $hourlyRate,
            'multiplier' => $multiplier,
            'overtime_hour_rate' => $overtimeHourRate,
            'baseline_hours' => $baselineHours,
            'hours' => $hours,
            'adjusted' => $adjusted,
            'overtime_days' => $summaries->count(),
            'pay' => $pay,
            'days' => $days,
        ];
    }
}
