<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\Payroll\EosbService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EosbTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): EosbService
    {
        return app(EosbService::class);
    }

    private function employee(array $overrides = []): Employee
    {
        $company = Company::firstOrCreate(['code' => 'EOSB'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create(array_merge([
            'company_id' => $company->id, 'name_ar' => 'م', 'name_en' => 'Emp',
            'employee_code' => 'EOSB-1', 'hr_employee_id' => 'HR-EOSB-1', 'national_id' => '1700000001',
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'EOSB-P', 'status' => 'active',
            'basic_salary' => 8000, 'housing_allowance' => 2000, 'transportation_allowance' => 0, 'other_allowances' => 0,
            'start_date' => '2016-01-01',
        ], $overrides));
    }

    public function test_full_wage_is_sum_of_components(): void
    {
        $this->assertSame(10000.0, $this->svc()->monthlyWage($this->employee()));
    }

    public function test_termination_award_half_then_full_month(): void
    {
        // 10 years @ 10,000: first 5y = 5×½×10000 = 25,000; next 5y = 5×1×10000 = 50,000.
        $r = $this->svc()->compute(10000, 10, EosbService::REASON_TERMINATION);

        $this->assertSame(25000.0, $r['first_amount']);
        $this->assertSame(50000.0, $r['later_amount']);
        $this->assertSame(75000.0, $r['award']);
    }

    public function test_resignation_scaling_bands(): void
    {
        // 3 years @ 10,000 base = 3×½×10000 = 15,000; resignation 2–5y → ⅓ = 5,000.
        $r3 = $this->svc()->compute(10000, 3, EosbService::REASON_RESIGNATION);
        $this->assertEqualsWithDelta(5000.0, $r3['award'], 0.01);
        $this->assertSame('third', $r3['scale_label']);

        // 7 years base = 5×½×10000 + 2×1×10000 = 45,000; 5–10y → ⅔ = 30,000.
        $r7 = $this->svc()->compute(10000, 7, EosbService::REASON_RESIGNATION);
        $this->assertEqualsWithDelta(30000.0, $r7['award'], 0.01);
        $this->assertSame('two_thirds', $r7['scale_label']);

        // 12 years → full award.
        $r12 = $this->svc()->compute(10000, 12, EosbService::REASON_RESIGNATION);
        $this->assertSame('full', $r12['scale_label']);
    }

    public function test_resignation_under_two_years_no_award(): void
    {
        $r = $this->svc()->compute(10000, 1.5, EosbService::REASON_RESIGNATION);

        $this->assertSame(0.0, $r['award']);
        $this->assertFalse($r['eligible']);
    }

    public function test_for_employee_uses_service_dates(): void
    {
        $emp = $this->employee(['start_date' => '2020-01-01']);
        $r = $this->svc()->forEmployee($emp, Carbon::parse('2025-01-01'), EosbService::REASON_TERMINATION);

        $this->assertEqualsWithDelta(5.0, $r['years'], 0.02);
        // 5 years @ 10,000, all in first band = 5×½×10000 = 25,000.
        $this->assertEqualsWithDelta(25000.0, $r['award'], 50);
    }

    public function test_calculator_route_loads(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $emp = $this->employee();

        $this->get(route('eosb.calculator'))->assertOk();
        $this->get(route('eosb.calculator', ['employee_id' => $emp->id, 'end_date' => '2026-01-01', 'reason' => 'termination']))
            ->assertOk()->assertSee($emp->employee_code);
    }
}
