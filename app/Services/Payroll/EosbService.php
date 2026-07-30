<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use Carbon\Carbon;

/**
 * End-of-Service Benefit (مكافأة نهاية الخدمة) — Saudi Labor Law arts. 84–85.
 *
 *   - ½ month's wage for each of the first 5 years of service.
 *   - 1 month's wage for each year beyond 5, partial years prorated.
 *   - On resignation the award is scaled (art. 85): < 2y none, 2–5y ⅓,
 *     5–10y ⅔, ≥ 10y full. On employer termination / contract end: full.
 *
 * The "wage" is the last full wage (configurable components).
 */
class EosbService
{
    public const REASON_TERMINATION = 'termination';

    public const REASON_RESIGNATION = 'resignation';

    public function monthlyWage(Employee $employee): float
    {
        $components = (array) config('payroll.eosb.wage_components', ['basic_salary']);

        return array_sum(array_map(fn ($c) => (float) $employee->{$c}, $components));
    }

    /**
     * @return array<string, mixed>
     */
    public function forEmployee(Employee $employee, ?Carbon $endDate = null, string $reason = self::REASON_TERMINATION): array
    {
        $end = $endDate ? $endDate->copy() : ($employee->end_date ? Carbon::parse($employee->end_date) : Carbon::today());
        $start = $employee->start_date ? Carbon::parse($employee->start_date) : $end->copy();

        $wage = $this->monthlyWage($employee);
        $years = $start->lessThan($end) ? $start->floatDiffInYears($end) : 0.0;

        $result = $this->compute($wage, $years, $reason);
        $result['start_date'] = $start;
        $result['end_date'] = $end;
        $result['service'] = $this->serviceBreakdown($start, $end);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function compute(float $wage, float $years, string $reason): array
    {
        $threshold = (int) config('payroll.eosb.first_years_threshold', 5);
        $firstRate = (float) config('payroll.eosb.first_years_rate', 0.5);
        $laterRate = (float) config('payroll.eosb.later_years_rate', 1.0);

        $firstYears = min($years, $threshold);
        $laterYears = max(0.0, $years - $threshold);

        $firstAmount = round($firstYears * $firstRate * $wage, 2);
        $laterAmount = round($laterYears * $laterRate * $wage, 2);
        $baseAward = round($firstAmount + $laterAmount, 2);

        [$scale, $scaleLabel] = $reason === self::REASON_RESIGNATION
            ? $this->resignationScale($years)
            : [1.0, 'full'];

        return [
            'wage' => round($wage, 2),
            'years' => round($years, 4),
            'reason' => $reason,
            'first_years' => round($firstYears, 4),
            'later_years' => round($laterYears, 4),
            'first_amount' => $firstAmount,
            'later_amount' => $laterAmount,
            'base_award' => $baseAward,
            'scale' => $scale,
            'scale_label' => $scaleLabel,
            'award' => round($baseAward * $scale, 2),
            'eligible' => $baseAward * $scale > 0,
        ];
    }

    /**
     * Resignation scaling (art. 85). Returns [factor, label].
     *
     * @return array{0: float, 1: string}
     */
    private function resignationScale(float $years): array
    {
        return match (true) {
            $years < 2 => [0.0, 'none'],
            $years < 5 => [1 / 3, 'third'],
            $years < 10 => [2 / 3, 'two_thirds'],
            default => [1.0, 'full'],
        };
    }

    /**
     * @return array{years: int, months: int, days: int}
     */
    private function serviceBreakdown(Carbon $start, Carbon $end): array
    {
        if ($start->greaterThanOrEqualTo($end)) {
            return ['years' => 0, 'months' => 0, 'days' => 0];
        }

        $diff = $start->diff($end);

        return ['years' => $diff->y, 'months' => $diff->m, 'days' => $diff->d];
    }
}
