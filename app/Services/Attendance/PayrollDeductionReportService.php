<?php

namespace App\Services\Attendance;

use App\Models\AttendanceHoliday;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Payroll attendance-deduction report (policy sections 34–35, 38).
 *
 * For an employee (or a whole company) over a payroll month it runs the per-day
 * evaluator across every date, honours the calendar (weekly rest, holidays,
 * approved leaves incur no deduction), converts hours/penalty-days into money via
 * PayrollDeductionService, and returns totals plus a day-by-day breakdown.
 */
class PayrollDeductionReportService
{
    public function __construct(
        private readonly AttendanceDeductionEvaluator $evaluator,
        private readonly PayrollDeductionService $money,
        private readonly AttendanceMatrixService $matrix,
        private readonly AttendancePolicyService $policies,
    ) {
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forCompany(Company $company, Carbon $from, Carbon $to): Collection
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $employees = Employee::where('company_id', $company->id)->with('shift')->orderBy('name_en')->get();
        $holidays = AttendanceHoliday::where('is_active', true)->whereBetween('holiday_date', [$start, $end])->get();
        $leaves = EmployeeLeaveRequest::where('status', 'approved')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)
            ->get();

        return $employees->map(fn (Employee $e) => $this->forEmployee($e, $from, $to, $holidays, $leaves));
    }

    /**
     * Deduction report for one employee over any from–to pay period.
     *
     * @param  Collection<int, AttendanceHoliday>  $holidays
     * @param  Collection<int, EmployeeLeaveRequest>  $leaves
     * @return array<string, mixed>
     */
    public function forEmployee(Employee $employee, Carbon $from, Carbon $to, Collection $holidays, Collection $leaves): array
    {
        $employee->loadMissing('shift');
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay()->min(Carbon::today()->endOfDay());

        $weekend = $this->policies->forEmployee($employee)->weekend_days ?? [5];

        $punchesByDay = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('punch_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderBy('punch_at')
            ->get()
            ->groupBy(fn (AttendanceRecord $r) => $r->punch_at->toDateString());

        $salaryBasis = $this->money->salaryBasis($employee);
        // Scheduled hours span the whole planned period (not just elapsed days).
        $scheduledMinutes = $this->money->scheduledMinutes($employee, $from->copy(), $to->copy(), $holidays, $leaves);
        $hourlyRate = $this->money->hourlyRate($salaryBasis, $scheduledMinutes);
        $dailyRate = $this->money->dailyRate($salaryBasis);

        $lateHours = $earlyHours = $missingHours = $penaltyDays = 0;
        $reviewCount = 0;
        $days = [];

        // Never evaluate days before the employee joined.
        $iterationStart = $employee->start_date
            ? $start->copy()->max(Carbon::parse($employee->start_date)->startOfDay())
            : $start->copy();

        for ($date = $iterationStart; $date->lte($end); $date->addDay()) {
            $calendar = $this->matrix->calendarStatus($employee, $date, $holidays, $leaves); // leave | holiday | null
            if ($calendar === null && in_array($date->dayOfWeek, $weekend, true)) {
                $calendar = 'rest';
            }

            $eval = $this->evaluator->evaluate($employee, $date, $punchesByDay->get($date->toDateString(), collect()), $calendar);

            $dayLate = array_sum(array_column($eval['shifts'], 'late_deduction_hours'));
            $dayEarly = array_sum(array_column($eval['shifts'], 'early_deduction_hours'));
            $dayMissing = array_sum(array_column($eval['shifts'], 'missing_punch_hours'));

            $lateHours += $dayLate;
            $earlyHours += $dayEarly;
            $missingHours += $dayMissing;
            $penaltyDays += $eval['absence']['penalty_days'];
            if ($eval['needs_review']) {
                $reviewCount++;
            }

            // Only keep days that actually carry a deduction or need review.
            if ($dayLate || $dayEarly || $dayMissing || $eval['absence']['penalty_days'] || $eval['needs_review']) {
                $eval['amounts'] = [
                    'late' => $this->money->hourAmount($dayLate, $hourlyRate),
                    'early' => $this->money->hourAmount($dayEarly, $hourlyRate),
                    'missing' => $this->money->hourAmount($dayMissing, $hourlyRate),
                    'absence' => $this->money->absenceAmount($eval['absence']['penalty_days'], $dailyRate),
                ];
                $days[] = $eval;
            }
        }

        $lateAmount = $this->money->hourAmount($lateHours, $hourlyRate);
        $earlyAmount = $this->money->hourAmount($earlyHours, $hourlyRate);
        $missingAmount = $this->money->hourAmount($missingHours, $hourlyRate);
        $absenceAmount = $this->money->absenceAmount($penaltyDays, $dailyRate);

        // Administrative penalties recorded in the period also reduce net pay.
        $penalties = \App\Models\AdministrativePenalty::active()
            ->where('employee_id', $employee->id)
            ->whereBetween('penalty_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();
        $penaltyAmount = round((float) $penalties->sum('amount'), 2);

        $totalDeduction = round($lateAmount + $earlyAmount + $missingAmount + $absenceAmount + $penaltyAmount, 2);

        return [
            'employee' => $employee,
            'salary_basis' => round($salaryBasis, 2),
            'scheduled_hours' => round($scheduledMinutes / 60, 1),
            'hourly_rate' => $hourlyRate,
            'daily_rate' => $dailyRate,
            'late_hours' => $lateHours,
            'early_hours' => $earlyHours,
            'missing_hours' => $missingHours,
            'penalty_days' => $penaltyDays,
            'late_amount' => $lateAmount,
            'early_amount' => $earlyAmount,
            'missing_amount' => $missingAmount,
            'absence_amount' => $absenceAmount,
            'penalty_count' => $penalties->count(),
            'penalty_amount' => $penaltyAmount,
            'total_deduction' => $totalDeduction,
            'net_salary' => round($salaryBasis - $totalDeduction, 2),
            'review_count' => $reviewCount,
            'days' => $days,
        ];
    }
}
