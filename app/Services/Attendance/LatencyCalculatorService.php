<?php

namespace App\Services\Attendance;

use App\Models\AttendanceHoliday;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Models\LatencyPolicy;
use Carbon\Carbon;

/**
 * Manual "Latency Calculator": for a single employee and date range it pulls
 * the attendance records, computes each day's late minutes and absence via the
 * shared engine, then re-prices the late component using the employee's
 * assigned {@see LatencyPolicy} (grace / round-up / multiplier). Absence keeps
 * the standard one/two-shift rule and the payroll money conversion.
 */
class LatencyCalculatorService
{
    public function __construct(
        private readonly AttendanceDeductionEvaluator $evaluator,
        private readonly AttendanceMatrixService $matrix,
        private readonly AttendancePolicyService $policies,
        private readonly PayrollDeductionService $money,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function calculate(Employee $employee, Carbon $from, Carbon $to, ?LatencyPolicy $policy = null): array
    {
        $employee->loadMissing('shift');
        $policy ??= $employee->effectiveLatencyPolicy();

        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay()->min(Carbon::today()->endOfDay());

        $weekend = $this->policies->forEmployee($employee)->weekend_days ?? [5];

        $holidays = AttendanceHoliday::where('is_active', true)
            ->whereBetween('holiday_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->get();
        $leaves = EmployeeLeaveRequest::where('status', 'approved')
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)->get();

        $punchesByDay = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('punch_at', [$start, $end])
            ->orderBy('punch_at')
            ->get()
            ->groupBy(fn (AttendanceRecord $r) => $r->punch_at->toDateString());

        $salaryBasis = $this->money->salaryBasis($employee);
        $scheduledMinutes = $this->money->scheduledMinutes($employee, $from->copy(), $to->copy(), $holidays, $leaves);
        $hourlyRate = $this->money->hourlyRate($salaryBasis, $scheduledMinutes);
        $dailyRate = $this->money->dailyRate($salaryBasis);

        $iterationStart = $employee->start_date
            ? $start->copy()->max(Carbon::parse($employee->start_date)->startOfDay())
            : $start->copy();

        $lateHours = 0.0;
        $lateMinutesTotal = 0;
        $penaltyDays = 0;
        $lateDays = 0;
        $absentDays = 0;
        $days = [];

        for ($date = $iterationStart->copy(); $date->lte($end); $date->addDay()) {
            $calendar = $this->matrix->calendarStatus($employee, $date, $holidays, $leaves);
            if ($calendar === null && in_array($date->dayOfWeek, $weekend, true)) {
                $calendar = 'rest';
            }

            $eval = $this->evaluator->evaluate($employee, $date, $punchesByDay->get($date->toDateString(), collect()), $calendar);

            // Re-price the late component with the chosen policy, per shift.
            $dayLateHours = 0.0;
            $dayLateMinutes = 0;
            foreach ($eval['shifts'] as $shift) {
                $dayLateMinutes += (int) $shift['late_minutes'];
                $dayLateHours += $policy->lateDeductionHours((int) $shift['late_minutes']);
            }

            $dayPenaltyDays = (int) $eval['absence']['penalty_days'];

            $lateHours += $dayLateHours;
            $lateMinutesTotal += $dayLateMinutes;
            $penaltyDays += $dayPenaltyDays;
            if ($dayLateHours > 0) {
                $lateDays++;
            }
            if ($dayPenaltyDays > 0) {
                $absentDays++;
            }

            if ($dayLateHours > 0 || $dayPenaltyDays > 0) {
                $days[] = [
                    'date' => $date->copy(),
                    'late_minutes' => $dayLateMinutes,
                    'late_hours' => round($dayLateHours, 2),
                    'late_amount' => $this->money->hourAmount($dayLateHours, $hourlyRate),
                    'penalty_days' => $dayPenaltyDays,
                    'absence_amount' => $this->money->absenceAmount($dayPenaltyDays, $dailyRate),
                    'status' => $eval['status'],
                ];
            }
        }

        $lateAmount = $this->money->hourAmount($lateHours, $hourlyRate);
        $absenceAmount = $this->money->absenceAmount($penaltyDays, $dailyRate);

        return [
            'employee' => $employee,
            'policy' => $policy,
            'from' => $from->copy(),
            'to' => $to->copy(),
            'salary_basis' => round($salaryBasis, 2),
            'hourly_rate' => $hourlyRate,
            'daily_rate' => $dailyRate,
            'late_minutes_total' => $lateMinutesTotal,
            'late_hours' => round($lateHours, 2),
            'late_amount' => $lateAmount,
            'late_days' => $lateDays,
            'penalty_days' => $penaltyDays,
            'absent_days' => $absentDays,
            'absence_amount' => $absenceAmount,
            'total_deduction' => round($lateAmount + $absenceAmount, 2),
            'days' => $days,
        ];
    }
}
