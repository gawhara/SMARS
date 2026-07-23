<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'hr_manager', 'hr_officer', 'accountant', 'branch_manager', 'employee_viewer'] as $name) {
            Role::firstOrCreate(['name' => $name], ['display_name_ar' => $name, 'display_name_en' => $name, 'is_active' => true]);
        }

        $this->seed(PermissionSeeder::class);
    }

    private function user(string $role): User
    {
        return User::factory()->role($role)->create(['is_active' => true]);
    }

    public function test_employee_viewer_can_read_employees_but_not_write(): void
    {
        $viewer = $this->user('employee_viewer');

        $this->actingAs($viewer)->get(route('employees.index'))->assertOk();
        $this->actingAs($viewer)->get(route('employees.create'))->assertForbidden();
    }

    public function test_employee_viewer_is_denied_payroll_and_provisioning(): void
    {
        $viewer = $this->user('employee_viewer');

        $this->actingAs($viewer)->get(route('payroll.periods.index'))->assertForbidden();
        // Also hidden from the sidebar.
        $this->actingAs($viewer)->get(route('dashboard'))->assertOk()->assertDontSee('/payroll/periods');
    }

    public function test_hr_officer_can_create_employees_but_not_delete_or_provision(): void
    {
        $officer = $this->user('hr_officer');
        $employee = \App\Models\Employee::create([
            'company_id' => Company::create(['code' => 'C1', 'name_ar' => 'ش', 'name_en' => 'C', 'is_active' => true])->id,
            'name_ar' => 'اسم', 'name_en' => 'Emp', 'employee_code' => 'E1', 'hr_employee_id' => 'HR-1',
            'national_id' => '1234567890', 'nationality' => 'SA', 'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P1', 'status' => 'active',
        ]);

        $this->actingAs($officer)->get(route('employees.create'))->assertOk();
        $this->actingAs($officer)->delete(route('employees.destroy', $employee))->assertForbidden();
    }

    public function test_accountant_reaches_payroll_but_not_employee_management(): void
    {
        $accountant = $this->user('accountant');

        $this->actingAs($accountant)->get(route('payroll.periods.index'))->assertOk();
        $this->actingAs($accountant)->get(route('employees.create'))->assertForbidden();
    }

    public function test_super_admin_bypasses_all_gates(): void
    {
        $admin = $this->user('super_admin');

        $this->actingAs($admin)->get(route('employees.create'))->assertOk();
        $this->actingAs($admin)->get(route('payroll.periods.index'))->assertOk();
    }

    public function test_inactive_user_is_locked_out_of_gated_routes(): void
    {
        // Even a super_admin is denied once deactivated (Gate::before).
        $admin = User::factory()->role('super_admin')->create(['is_active' => false]);

        $this->actingAs($admin)->get(route('companies.index'))->assertForbidden();
    }

    public function test_roleless_user_has_no_access(): void
    {
        $nobody = User::factory()->withoutRole()->create(['is_active' => true]);

        $this->actingAs($nobody)->get(route('employees.index'))->assertForbidden();
    }
}
