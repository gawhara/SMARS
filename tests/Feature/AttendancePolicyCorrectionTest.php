<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceDailySummary;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePolicyCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $company = Company::create(['name_ar' => 'شركة', 'name_en' => 'Company', 'code' => 'POL', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'موظف', 'name_en' => 'Employee',
            'employee_code' => 'POL-1', 'hr_employee_id' => 'HR-POL-1',
            'national_id' => '1234567891', 'nationality' => 'SA', 'saudi_non_saudi' => 'saudi',
            'passport_id' => 'PASS-POL-1', 'status' => 'active',
        ]);
    }

    public function test_company_attendance_policy_can_be_configured(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee();

        $this->actingAs($user)->put(route('attendance.policies.update', $employee->company), [
            'grace_minutes' => 15,
            'early_leave_grace_minutes' => 5,
            'full_day_minutes' => 480,
            'half_day_minutes' => 240,
            'overtime_after_minutes' => 450,
            'rounding_minutes' => 5,
            'weekend_days' => [5, 6],
            'is_active' => '1',
        ])->assertRedirect(route('attendance.policies.index'));

        $this->assertDatabaseHas('attendance_policies', [
            'company_id' => $employee->company_id,
            'grace_minutes' => 15,
            'overtime_after_minutes' => 450,
        ]);
    }

    public function test_policy_and_correction_management_pages_load(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->employee();

        $this->actingAs($user)->get(route('attendance.policies.index'))
            ->assertOk()->assertSee(__('app.att.policies'));
        $this->actingAs($user)->get(route('attendance.corrections.index'))
            ->assertOk()->assertSee(__('app.att.corrections'));
        $this->actingAs($user)->get(route('attendance.corrections.create'))
            ->assertOk()->assertSee(__('app.att.request_correction'));
    }

    public function test_policy_drives_overtime_in_daily_summary(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee();
        $employee->company->attendancePolicy()->create([
            'grace_minutes' => 10, 'early_leave_grace_minutes' => 0,
            'full_day_minutes' => 480, 'half_day_minutes' => 240,
            'overtime_after_minutes' => 480, 'rounding_minutes' => 1,
            'weekend_days' => [5], 'is_active' => true,
        ]);

        foreach ([['08:00', 'in'], ['17:00', 'out']] as [$time, $type]) {
            $this->actingAs($user)->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'punch_at' => "2026-07-14T{$time}",
                'punch_type' => $type,
            ]);
        }

        $summary = AttendanceDailySummary::firstOrFail();
        $this->assertSame(540, $summary->worked_minutes);
        $this->assertSame(60, $summary->overtime_minutes);
    }

    public function test_approved_correction_preserves_original_and_recalculates_summary(): void
    {
        $requester = User::factory()->create(['is_active' => true]);
        $reviewer = User::factory()->create(['is_active' => true]);
        $employee = $this->employee();

        $this->actingAs($requester)->post(route('attendance.store'), [
            'employee_id' => $employee->id,
            'punch_at' => '2026-07-15T08:30',
            'punch_type' => 'in',
        ]);
        $record = AttendanceRecord::firstOrFail();

        $this->actingAs($requester)->post(route('attendance.corrections.store'), [
            'employee_id' => $employee->id,
            'attendance_record_id' => $record->id,
            'requested_punch_at' => '2026-07-15T08:00',
            'requested_punch_type' => 'in',
            'reason' => 'Device clock was incorrect.',
        ])->assertRedirect(route('attendance.corrections.index'));

        $correction = AttendanceCorrectionRequest::firstOrFail();
        $this->actingAs($reviewer)->put(route('attendance.corrections.approve', $correction), [
            'review_notes' => 'Verified with supervisor.',
        ])->assertRedirect();

        $this->assertSoftDeleted('attendance_records', ['id' => $record->id]);
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correction->id, 'status' => 'approved', 'reviewed_by' => $reviewer->id,
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $employee->id, 'punch_at' => '2026-07-15 08:00:00', 'source' => 'correction',
        ]);
        $this->assertSame('08:00', AttendanceDailySummary::firstOrFail()->first_in_at->format('H:i'));
    }

    public function test_rejected_correction_does_not_change_original_punch(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee();
        $record = AttendanceRecord::create(['employee_id' => $employee->id, 'punch_at' => '2026-07-16 08:30:00', 'punch_type' => 'in']);
        $correction = AttendanceCorrectionRequest::create([
            'employee_id' => $employee->id, 'attendance_record_id' => $record->id,
            'original_punch_at' => $record->punch_at, 'original_punch_type' => 'in',
            'requested_punch_at' => '2026-07-16 08:00:00', 'requested_punch_type' => 'in',
            'reason' => 'Requested change.', 'status' => 'pending', 'requested_by' => $user->id,
        ]);

        $this->actingAs($user)->put(route('attendance.corrections.reject', $correction), ['review_notes' => 'Not verified.'])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_records', ['id' => $record->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('attendance_correction_requests', ['id' => $correction->id, 'status' => 'rejected']);
    }
}
