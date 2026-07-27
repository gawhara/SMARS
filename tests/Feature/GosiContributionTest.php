<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Services\Payroll\GosiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GosiContributionTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): GosiService
    {
        return app(GosiService::class);
    }

    private function employee(array $overrides = []): Employee
    {
        $company = Company::firstOrCreate(['code' => 'GOSI'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create(array_merge([
            'company_id' => $company->id, 'name_ar' => 'م', 'name_en' => 'Emp',
            'employee_code' => 'GOSI-1', 'hr_employee_id' => 'HR-GOSI-1', 'national_id' => '1090000001',
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'GOSI-P', 'status' => 'active',
            'basic_salary_gosi' => 8000, 'housing_allowance_gosi' => 2000,
        ], $overrides));
    }

    public function test_saudi_contribution_uses_standard_rates(): void
    {
        // Wage 10,000 → employee 9.75% = 975, employer 11.75% = 1175.
        $g = $this->svc()->forEmployee($this->employee());

        $this->assertTrue($g['is_saudi']);
        $this->assertSame(10000.0, $g['contribution_wage']);
        $this->assertSame(975.0, $g['employee_share']);
        $this->assertSame(1175.0, $g['employer_share']);
        $this->assertSame(2150.0, $g['total']);
        $this->assertFalse($g['capped']);
    }

    public function test_non_saudi_only_employer_occupational_hazard(): void
    {
        $g = $this->svc()->forEmployee($this->employee([
            'employee_code' => 'GOSI-2', 'hr_employee_id' => 'HR-GOSI-2',
            'national_id' => '2090000002', 'passport_id' => 'GOSI-P2',
            'nationality' => 'EG', 'saudi_non_saudi' => 'non_saudi',
        ]));

        $this->assertFalse($g['is_saudi']);
        $this->assertSame(0.0, $g['employee_share']);
        $this->assertSame(200.0, $g['employer_share']); // 10,000 × 2%
        $this->assertSame(200.0, $g['total']);
    }

    public function test_wage_is_capped_at_ceiling(): void
    {
        $g = $this->svc()->forEmployee($this->employee([
            'employee_code' => 'GOSI-3', 'hr_employee_id' => 'HR-GOSI-3',
            'national_id' => '1090000003', 'passport_id' => 'GOSI-P3',
            'basic_salary_gosi' => 50000, 'housing_allowance_gosi' => 10000,
        ]));

        $this->assertTrue($g['capped']);
        $this->assertSame(45000.0, $g['contribution_wage']); // clamped from 60,000
        $this->assertSame(4387.5, $g['employee_share']); // 45,000 × 9.75%
    }

    public function test_wage_is_lifted_to_floor(): void
    {
        $g = $this->svc()->forEmployee($this->employee([
            'employee_code' => 'GOSI-4', 'hr_employee_id' => 'HR-GOSI-4',
            'national_id' => '1090000004', 'passport_id' => 'GOSI-P4',
            'basic_salary_gosi' => 800, 'housing_allowance_gosi' => 0,
        ]));

        $this->assertSame(1500.0, $g['contribution_wage']); // lifted from 800 to floor
    }

    public function test_unregistered_wage_yields_no_contribution(): void
    {
        $g = $this->svc()->forEmployee($this->employee([
            'employee_code' => 'GOSI-5', 'hr_employee_id' => 'HR-GOSI-5',
            'national_id' => '1090000005', 'passport_id' => 'GOSI-P5',
            'basic_salary_gosi' => 0, 'housing_allowance_gosi' => 0,
        ]));

        $this->assertSame(0.0, $g['contribution_wage']);
        $this->assertSame(0.0, $g['total']);
    }
}
