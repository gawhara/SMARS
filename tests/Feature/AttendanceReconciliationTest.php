<?php

namespace Tests\Feature;

use App\Models\AttendanceDailySummary;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $company = Company::create([
            'name_ar' => 'شركة الاختبار',
            'name_en' => 'Test Company',
            'code' => 'RECON',
            'is_active' => true,
        ]);

        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'سلمان العتيبي',
            'name_en' => 'Salman Alotaibi',
            'employee_code' => 'EMP-9001',
            'hr_employee_id' => '9001',
            'national_id' => '1099999999',
            'nationality' => 'SA',
            'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P-9001',
            'status' => 'active',
        ]);
    }

    private function exceptionSummary(Employee $employee): AttendanceDailySummary
    {
        return AttendanceDailySummary::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-06-15',
            'first_in_at' => '2026-06-15 08:00:00',
            'punch_count' => 1,
            'worked_minutes' => 0,
            'scheduled_minutes' => 480,
            'status' => 'incomplete',
            'has_exception' => true,
            'exception_codes' => ['missing_out'],
            'reconciliation_status' => 'open',
            'calculated_at' => now(),
        ]);
    }

    public function test_reconciliation_dashboard_lists_open_exception_days(): void
    {
        $employee = $this->employee();
        $this->exceptionSummary($employee);

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('attendance.reconciliation.index'))
            ->assertOk()
            ->assertSee(__('app.recon.title'))
            ->assertSee('9001')
            ->assertSee(__('app.att.ex_missing_out'));
    }

    public function test_reviewer_can_bulk_approve_and_reopen_exception_days(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $summary = $this->exceptionSummary($this->employee());

        $this->actingAs($user)->put(route('attendance.reconciliation.approve'), [
            'summary_ids' => [$summary->id],
            'notes' => 'Reviewed against the source punches.',
        ])->assertRedirect();

        $summary->refresh();
        $this->assertSame('approved', $summary->reconciliation_status);
        $this->assertSame($user->id, $summary->reconciled_by);
        $this->assertNotNull($summary->reconciled_at);
        $this->assertSame('Reviewed against the source punches.', $summary->reconciliation_notes);

        $this->actingAs($user)->put(route('attendance.reconciliation.reopen'), [
            'summary_ids' => [$summary->id],
        ])->assertRedirect();

        $summary->refresh();
        $this->assertSame('open', $summary->reconciliation_status);
        $this->assertNull($summary->reconciled_by);
        $this->assertNull($summary->reconciled_at);
    }

    public function test_locked_payroll_period_cannot_be_approved_or_reopened(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee();
        $summary = $this->exceptionSummary($employee);
        PayrollPeriod::create([
            'company_id' => $employee->company_id,
            'period_month' => '2026-06-01',
            'status' => 'locked',
            'locked_at' => now(),
            'locked_by' => $user->id,
        ]);

        $this->actingAs($user)->put(route('attendance.reconciliation.approve'), [
            'summary_ids' => [$summary->id],
        ])->assertRedirect();
        $this->assertSame('open', $summary->fresh()->reconciliation_status);

        $summary->update([
            'reconciliation_status' => 'approved',
            'reconciled_by' => $user->id,
            'reconciled_at' => now(),
        ]);
        $this->actingAs($user)->put(route('attendance.reconciliation.reopen'), [
            'summary_ids' => [$summary->id],
        ])->assertRedirect();
        $this->assertSame('approved', $summary->fresh()->reconciliation_status);
    }
}
