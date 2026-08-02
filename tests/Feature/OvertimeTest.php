<?php

namespace Tests\Feature;

use App\Models\AttendanceDailySummary;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Services\Payroll\OvertimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OvertimeTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $company = Company::firstOrCreate(['code' => 'OT'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);
        $schedule = (string) Str::uuid();
        $shift = Shift::create(['schedule_id' => $schedule, 'shift_number' => 1, 'schedule_name_ar' => 'ج', 'name_ar' => 'ش'.$schedule, 'name_en' => 'S'.$schedule, 'start_time' => '08:00', 'end_time' => '16:00', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id, 'name_ar' => 'م', 'name_en' => 'Emp',
            'employee_code' => 'OT-1', 'hr_employee_id' => 'HR-OT-1', 'national_id' => '1900000001',
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'OT-P', 'status' => 'active',
            'shift_id' => $shift->id, 'basic_salary_gosi' => 6000, 'housing_allowance_gosi' => 0,
        ]);
    }

    private function overtimeDay(Employee $e, string $date, int $otMinutes): void
    {
        AttendanceDailySummary::create([
            'employee_id' => $e->id, 'attendance_date' => $date,
            'worked_minutes' => 480 + $otMinutes, 'scheduled_minutes' => 480,
            'late_minutes' => 0, 'early_leave_minutes' => 0, 'overtime_minutes' => $otMinutes,
            'punch_count' => 2, 'status' => 'present', 'present' => true,
        ]);
    }

    public function test_baseline_hours_and_pay_at_150_percent(): void
    {
        $e = $this->employee();
        $this->overtimeDay($e, '2026-06-01', 120); // 2h
        $this->overtimeDay($e, '2026-06-02', 60);  // 1h

        $r = app(OvertimeService::class)->forEmployee($e, Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'));

        $this->assertSame(3.0, $r['baseline_hours']);
        $this->assertSame(3.0, $r['hours']);
        $this->assertFalse($r['adjusted']);
        $this->assertSame(1.5, $r['multiplier']);
        // pay = 3h × hourlyRate × 1.5
        $this->assertEqualsWithDelta(3 * $r['hourly_rate'] * 1.5, $r['pay'], 0.01);
        $this->assertSame(2, $r['overtime_days']);
    }

    public function test_manual_hours_override_wins(): void
    {
        $e = $this->employee();
        $this->overtimeDay($e, '2026-06-01', 120);

        $r = app(OvertimeService::class)->forEmployee($e, Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'), 5.0);

        $this->assertTrue($r['adjusted']);
        $this->assertSame(5.0, $r['hours']);
        $this->assertSame(2.0, $r['baseline_hours']);
        $this->assertEqualsWithDelta(5 * $r['hourly_rate'] * 1.5, $r['pay'], 0.01);
    }

    public function test_custom_multiplier(): void
    {
        $e = $this->employee();
        $this->overtimeDay($e, '2026-06-01', 60);

        $r = app(OvertimeService::class)->forEmployee($e, Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'), null, 2.0);

        $this->assertSame(2.0, $r['multiplier']);
        $this->assertEqualsWithDelta($r['hourly_rate'] * 2.0, $r['overtime_hour_rate'], 0.001);
    }

    public function test_calculator_route_loads(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $e = $this->employee();
        $this->overtimeDay($e, '2026-06-01', 120);

        $this->get(route('overtime.calculator'))->assertOk();
        $this->get(route('overtime.calculator', ['employee_id' => $e->id, 'date_from' => '2026-06-01', 'date_to' => '2026-06-30', 'hours' => 4]))
            ->assertOk()->assertSee($e->employee_code);
    }
}
