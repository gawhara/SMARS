<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LatencyPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatencyPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function employee(array $overrides = []): Employee
    {
        $company = Company::firstOrCreate(['code' => 'LAT'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create(array_merge([
            'company_id' => $company->id, 'name_ar' => 'م', 'name_en' => 'Emp',
            'employee_code' => 'LAT-1', 'hr_employee_id' => 'HR-LAT-1', 'national_id' => '1500000001',
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'LAT-P', 'status' => 'active',
            'basic_salary_gosi' => 6000, 'housing_allowance_gosi' => 0,
        ], $overrides));
    }

    public function test_grace_and_round_up_and_multiplier(): void
    {
        $policy = new LatencyPolicy(['grace_minutes' => 9, 'round_up_to_hour' => true, 'multiplier' => 2]);

        $this->assertSame(0.0, $policy->lateDeductionHours(9));   // within grace
        $this->assertSame(2.0, $policy->lateDeductionHours(10));  // ceil(10/60)=1h ×2
        $this->assertSame(4.0, $policy->lateDeductionHours(65));  // ceil(65/60)=2h ×2
    }

    public function test_exact_minutes_when_rounding_off(): void
    {
        $policy = new LatencyPolicy(['grace_minutes' => 0, 'round_up_to_hour' => false, 'multiplier' => 1]);

        // 30 min = 0.5h × 1
        $this->assertSame(0.5, $policy->lateDeductionHours(30));
    }

    public function test_effective_policy_prefers_assignment_then_default(): void
    {
        $assigned = LatencyPolicy::create(['name' => 'Strict', 'grace_minutes' => 0, 'multiplier' => 3, 'is_active' => true]);
        $default = LatencyPolicy::create(['name' => 'Default', 'grace_minutes' => 15, 'multiplier' => 1, 'is_default' => true, 'is_active' => true]);

        $withPolicy = $this->employee(['latency_policy_id' => $assigned->id]);
        $this->assertTrue($withPolicy->effectiveLatencyPolicy()->is($assigned));

        $withoutPolicy = $this->employee(['employee_code' => 'LAT-2', 'hr_employee_id' => 'HR-LAT-2', 'national_id' => '1500000002', 'passport_id' => 'LAT-P2']);
        $this->assertTrue($withoutPolicy->effectiveLatencyPolicy()->is($default));
    }

    public function test_default_policy_falls_back_to_engine_defaults(): void
    {
        $p = LatencyPolicy::defaultPolicy();
        $this->assertSame(9, $p->grace_minutes);
        $this->assertEqualsWithDelta(2.0, (float) $p->multiplier, 0.001);
    }

    public function test_store_enforces_single_default(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $first = LatencyPolicy::create(['name' => 'A', 'grace_minutes' => 5, 'multiplier' => 2, 'is_default' => true, 'is_active' => true]);

        $this->post(route('latency.policies.store'), [
            'name' => 'B', 'grace_minutes' => 10, 'multiplier' => 2, 'round_up_to_hour' => '1', 'is_default' => '1', 'is_active' => '1',
        ])->assertRedirect(route('latency.policies.index'));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertSame(1, LatencyPolicy::where('is_default', true)->count());
    }

    public function test_calculator_page_loads_and_computes_for_employee(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $employee = $this->employee();

        $this->get(route('latency.calculator'))->assertOk();

        $this->get(route('latency.calculator', [
            'employee_id' => $employee->id,
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]))->assertOk()->assertSee($employee->employee_code);
    }
}
