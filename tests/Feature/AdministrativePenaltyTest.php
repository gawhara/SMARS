<?php

namespace Tests\Feature;

use App\Models\AdministrativePenalty;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\Attendance\PayrollDeductionReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrativePenaltyTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $code = 'PEN-1'): Employee
    {
        $company = Company::firstOrCreate(['code' => 'PEN'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id, 'name_ar' => 'م', 'name_en' => 'Emp',
            'employee_code' => $code, 'hr_employee_id' => 'HR-'.$code, 'national_id' => '1'.substr((string) crc32($code).'000000000', 0, 9),
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'P-'.$code, 'status' => 'active',
            'basic_salary_gosi' => 5000, 'housing_allowance_gosi' => 1000, 'start_date' => '2026-06-01',
        ]);
    }

    public function test_penalty_is_recorded_and_audited(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee();

        $this->actingAs($user)->post(route('penalties.store'), [
            'employee_id' => $employee->id,
            'penalty_date' => '2026-06-01',
            'type' => 'fine',
            'reason' => 'Late repeatedly',
            'amount' => 150,
        ])->assertRedirect();

        $this->assertDatabaseHas('administrative_penalties', [
            'employee_id' => $employee->id, 'type' => 'fine', 'amount' => '150.00', 'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'penalty.created', 'user_id' => $user->id]);
    }

    public function test_active_penalty_reduces_net_salary_in_payroll(): void
    {
        $employee = $this->employee(); // no shift → no attendance deductions, isolates the penalty
        AdministrativePenalty::create([
            'employee_id' => $employee->id, 'penalty_date' => '2026-06-01', 'type' => 'fine',
            'reason' => 'x', 'amount' => 100, 'status' => 'active',
        ]);

        $row = app(PayrollDeductionReportService::class)
            ->forEmployee($employee->fresh(), Carbon::parse('2026-06-01'), Carbon::parse('2026-06-02'), collect(), collect());

        $this->assertSame(100.0, $row['penalty_amount']);
        $this->assertSame(100.0, $row['total_deduction']);
        $this->assertSame(5900.0, $row['net_salary']); // 6000 GOSI − 100 penalty
    }

    public function test_cancelled_penalty_does_not_affect_payroll(): void
    {
        $employee = $this->employee();
        AdministrativePenalty::create([
            'employee_id' => $employee->id, 'penalty_date' => '2026-06-01', 'type' => 'fine',
            'reason' => 'x', 'amount' => 100, 'status' => 'cancelled',
        ]);

        $row = app(PayrollDeductionReportService::class)
            ->forEmployee($employee->fresh(), Carbon::parse('2026-06-01'), Carbon::parse('2026-06-02'), collect(), collect());

        $this->assertSame(0.0, $row['penalty_amount']);
        $this->assertSame(6000.0, $row['net_salary']);
    }
}
