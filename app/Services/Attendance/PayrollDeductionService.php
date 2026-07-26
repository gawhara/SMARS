<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Money layer of the attendance-deduction policy (sections 26–33): turns the
 * deduction hours and penalty days from the evaluator into monetary amounts.
 *
 *   hourly rate = salary basis ÷ monthly scheduled hours          (section 28)
 *   daily rate  = salary basis ÷ fixed day divisor                (section 32)
 *   hour amount = deduction hours × hourly rate                   (sections 29–31)
 *   absence amt = penalty days × daily rate                       (section 33)
 */
class PayrollDeductionService
{
    public function __construct(private readonly AttendancePolicyService $policies)
    {
    }

    /** Approved salary basis for deductions (section 26). */
    public function salaryBasis(Employee $employee): float
    {
        return match (config('payroll.salary_basis', 'gosi')) {
            'basic' => (float) $employee->basic_salary,
            'full' => (float) $employee->basic_salary
                + (float) $employee->housing_allowance
                + (float) $employee->transportation_allowance
                + (float) $employee->other_allowances,
            default => (float) $employee->basic_salary_gosi + (float) $employee->housing_allowance_gosi,
        };
    }

    /** Paid minutes of all shifts the employee works in a day (no unpaid break, section 2). */
    public function dailyShiftMinutes(Employee $employee): int
    {
        $shift = $employee->shift;
        if (! $shift) {
            return 0;
        }

        $periods = $shift->schedule_id
            ? Shift::where('schedule_id', $shift->schedule_id)->get()
            : collect([$shift]);

        return (int) $periods->sum(fn (Shift $s) => $s->durationMinutes());
    }

    /**
     * Scheduled paid minutes for the payroll month (section 27): daily shift
     * minutes summed over the month's working days, excluding weekly rest days,
     * holidays, approved leaves, and days outside employment.
     *
     * @param  Collection<int, \App\Models\AttendanceHoliday>  $holidays
     * @param  Collection<int, \App\Models\EmployeeLeaveRequest>  $leaves
     */
    public function monthlyScheduledMinutes(Employee $employee, Carbon $month, Collection $holidays, Collection $leaves): int
    {
        $policy = $this->policies->forEmployee($employee);
        $weekend = $policy->weekend_days ?? [5];
        $daily = $this->dailyShiftMinutes($employee);

        if ($daily === 0) {
            return 0;
        }

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $joined = $employee->start_date ? Carbon::parse($employee->start_date) : null;

        $minutes = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($joined && $date->lt($joined)) {
                continue;
            }
            if (in_array($date->dayOfWeek, $weekend, true)) {
                continue;
            }
            if ($holidays->contains(fn ($h) => $h->holiday_date->isSameDay($date) && ($h->company_id === null || $h->company_id === $employee->company_id))) {
                continue;
            }
            if ($leaves->contains(fn ($l) => $l->employee_id === $employee->id && $date->betweenIncluded($l->start_date, $l->end_date))) {
                continue;
            }

            $minutes += $daily;
        }

        return $minutes;
    }

    /** Hourly deduction rate (section 28). */
    public function hourlyRate(float $salaryBasis, int $monthlyScheduledMinutes): float
    {
        $hours = $monthlyScheduledMinutes / 60;

        return $hours > 0 ? round($salaryBasis / $hours, 4) : 0.0;
    }

    /** Daily salary rate for absence penalties (section 32). */
    public function dailyRate(float $salaryBasis): float
    {
        $divisor = (int) config('payroll.day_divisor', 30);

        return $divisor > 0 ? round($salaryBasis / $divisor, 4) : 0.0;
    }

    /** Monetary amount for hour-based deductions — late, early, missing (sections 29–31). */
    public function hourAmount(int $deductionHours, float $hourlyRate): float
    {
        return round($deductionHours * $hourlyRate, 2);
    }

    /** Monetary amount for absence penalties (section 33). */
    public function absenceAmount(int $penaltyDays, float $dailyRate): float
    {
        return round($penaltyDays * $dailyRate, 2);
    }
}
