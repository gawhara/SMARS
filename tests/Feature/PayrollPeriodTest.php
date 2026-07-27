<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        return Company::firstOrCreate(['code' => 'CO1'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);
    }

    private function employee(Company $company): Employee
    {
        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'اسم', 'name_en' => 'Name',
            'employee_code' => 'EMP-1', 'hr_employee_id' => 'HR-1',
            'national_id' => '1234567890', 'nationality' => 'SA', 'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P1', 'status' => 'active', 'basic_salary' => 3000,
        ]);
    }

    public function test_index_ensures_current_period_and_can_lock(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->company();

        $this->actingAs($user)->get(route('payroll.periods.index'))->assertOk();

        $period = PayrollPeriod::firstOrFail();
        $this->assertSame('open', $period->status);

        $this->actingAs($user)->put(route('payroll.periods.lock', $period))->assertRedirect();
        $this->assertSame('locked', $period->refresh()->status);
        $this->assertSame($user->id, $period->locked_by);
    }

    public function test_locked_period_blocks_manual_punch(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $company = $this->company();
        $employee = $this->employee($company);

        PayrollPeriod::create([
            'company_id' => $company->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'status' => 'locked',
        ]);

        $this->actingAs($user)->post(route('attendance.store'), [
            'employee_id' => $employee->id,
            'punch_at' => now()->format('Y-m-d\T08:00'),
            'punch_type' => 'in',
        ])->assertSessionHas('error');

        $this->assertSame(0, AttendanceRecord::count());
    }

    public function test_unlocked_period_allows_punch(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $company = $this->company();
        $employee = $this->employee($company);

        // No locked period for this month.
        $this->actingAs($user)->post(route('attendance.store'), [
            'employee_id' => $employee->id,
            'punch_at' => now()->format('Y-m-d\T08:00'),
            'punch_type' => 'in',
        ])->assertRedirect(route('attendance.index'));

        $this->assertSame(1, AttendanceRecord::count());
    }

    public function test_payroll_export_streams_csv(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $company = $this->company();
        $this->employee($company);

        $period = PayrollPeriod::create([
            'company_id' => $company->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($user)->get(route('payroll.periods.export', $period));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Overtime hours', $content);
        $this->assertStringContainsString('EMP-1', $content);
        // Attendance deductions are folded into the payroll export.
        $this->assertStringContainsString('Total deductions', $content);
        $this->assertStringContainsString('Net salary', $content);
        $this->assertNotNull($period->refresh()->exported_at);
    }
}
