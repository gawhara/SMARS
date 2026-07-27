<?php

namespace App\Services\Payroll;

use App\Models\Employee;

/**
 * Computes the monthly GOSI (social insurance) contribution for an employee.
 *
 * The contribution wage is the GOSI-registered salary (basic + housing),
 * clamped to the statutory floor and ceiling, then split into employee and
 * employer shares using the rates for the employee's nationality class.
 * Rates and limits live in config/payroll.php ('gosi').
 */
class GosiService
{
    /**
     * @return array{
     *     wage: float,
     *     contribution_wage: float,
     *     is_saudi: bool,
     *     capped: bool,
     *     employee_rate: float,
     *     employer_rate: float,
     *     employee_share: float,
     *     employer_share: float,
     *     total: float
     * }
     */
    public function forEmployee(Employee $employee): array
    {
        $wage = (float) $employee->basic_salary_gosi + (float) $employee->housing_allowance_gosi;

        return $this->forWage($wage, $employee->saudi_non_saudi === 'saudi');
    }

    /**
     * Compute a contribution for a raw registered wage and nationality flag.
     */
    public function forWage(float $wage, bool $isSaudi): array
    {
        $config = config('payroll.gosi');
        $floor = (float) ($config['wage_floor'] ?? 0);
        $ceiling = (float) ($config['wage_ceiling'] ?? PHP_FLOAT_MAX);
        $rates = $isSaudi ? $config['saudi'] : $config['non_saudi'];

        // No registered wage means the employee is not enrolled yet: no contribution.
        $contributionWage = $wage <= 0 ? 0.0 : min(max($wage, $floor), $ceiling);

        $employeeRate = (float) $rates['employee_rate'];
        $employerRate = (float) $rates['employer_rate'];

        $employeeShare = round($contributionWage * $employeeRate, 2);
        $employerShare = round($contributionWage * $employerRate, 2);

        return [
            'wage' => round($wage, 2),
            'contribution_wage' => round($contributionWage, 2),
            'is_saudi' => $isSaudi,
            'capped' => $wage > $ceiling,
            'employee_rate' => $employeeRate,
            'employer_rate' => $employerRate,
            'employee_share' => $employeeShare,
            'employer_share' => $employerShare,
            'total' => round($employeeShare + $employerShare, 2),
        ];
    }
}
