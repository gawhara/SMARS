<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'hr_manager'] as $name) {
            Role::firstOrCreate(['name' => $name], ['display_name_ar' => $name, 'display_name_en' => $name, 'is_active' => true]);
        }

        $this->seed(PermissionSeeder::class);
    }

    private function company(): Company
    {
        return Company::create(['code' => 'C1', 'name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);
    }

    private function employee(Company $company): Employee
    {
        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'اسم', 'name_en' => 'Emp', 'employee_code' => 'E1', 'hr_employee_id' => 'HR-1',
            'national_id' => '1234567890', 'nationality' => 'SA', 'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P1', 'status' => 'active',
        ]);
    }

    public function test_payroll_lock_is_audited(): void
    {
        $admin = User::factory()->role('super_admin')->create(['is_active' => true]);
        $period = PayrollPeriod::create(['company_id' => $this->company()->id, 'period_month' => '2026-07-01', 'status' => 'open']);

        $this->actingAs($admin)->put(route('payroll.periods.lock', $period))->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll.locked',
            'user_id' => $admin->id,
            'auditable_type' => $period->getMorphClass(),
            'auditable_id' => $period->id,
        ]);
    }

    public function test_employee_delete_is_audited(): void
    {
        $admin = User::factory()->role('super_admin')->create(['is_active' => true]);
        $employee = $this->employee($this->company());

        $this->actingAs($admin)->delete(route('employees.destroy', $employee))->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.deleted',
            'user_id' => $admin->id,
            'auditable_id' => $employee->id,
        ]);
    }

    public function test_audit_page_loads_for_authorized_user(): void
    {
        $admin = User::factory()->role('super_admin')->create(['is_active' => true]);
        $employee = $this->employee($this->company());
        $this->actingAs($admin)->delete(route('employees.destroy', $employee));

        $this->actingAs($admin)->get(route('audit.logs.index'))
            ->assertOk()
            ->assertSee(__('app.audit.act_employee_deleted'));
    }

    public function test_audit_page_denied_without_permission(): void
    {
        // hr_manager is not granted audit.view.
        $manager = User::factory()->role('hr_manager')->create(['is_active' => true]);

        $this->actingAs($manager)->get(route('audit.logs.index'))->assertForbidden();
    }
}
