<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\Attendance\PayrollDeductionReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollDeductionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_totals_late_and_absence_into_amounts_and_net(): void
    {
        $company = Company::create(['name_ar' => 'ش', 'name_en' => 'Co', 'code' => 'RPT', 'is_active' => true]);
        $employee = Employee::create([
            'company_id' => $company->id, 'name_ar' => 'م', 'name_en' => 'Emp',
            'employee_code' => 'RPT-1', 'hr_employee_id' => 'HR-RPT-1', 'national_id' => '1234567898',
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'RPT-P', 'status' => 'active',
            'basic_salary_gosi' => 5000, 'housing_allowance_gosi' => 1000,
            'start_date' => '2026-06-01',
        ]);

        $schedule = (string) Str::uuid();
        $shift = Shift::create(['schedule_id' => $schedule, 'shift_number' => 1, 'schedule_name_ar' => 'ج', 'name_ar' => 'ش', 'name_en' => 'S-'.$schedule, 'start_time' => '08:00', 'end_time' => '17:00', 'is_active' => true]);
        $employee->update(['shift_id' => $shift->id]);

        // A custom 2-day pay period, fully in the past (so "today" doesn't clip it):
        // 2026-06-01 (Mon): 30 min late → 2 deduction hours.  2026-06-02 (Tue): no punches → absence.
        AttendanceRecord::create(['employee_id' => $employee->id, 'device_user_id' => 'HR-RPT-1', 'punch_at' => '2026-06-01 08:30', 'punch_type' => 'in', 'source' => 'manual', 'company_id' => $company->id]);
        AttendanceRecord::create(['employee_id' => $employee->id, 'device_user_id' => 'HR-RPT-1', 'punch_at' => '2026-06-01 17:00', 'punch_type' => 'out', 'source' => 'manual', 'company_id' => $company->id]);

        $row = app(PayrollDeductionReportService::class)->forEmployee($employee->fresh('shift'), Carbon::parse('2026-06-01'), Carbon::parse('2026-06-02'), collect(), collect());

        // Custom period = 2 working days × 9h = 18h. Rate = 6000 / 18 = 333.3333/hr.
        // Daily rate = 6000 / 30 = 200/day.
        $this->assertSame(18.0, $row['scheduled_hours']);
        $this->assertSame(6000.0, $row['salary_basis']);

        $this->assertSame(2, $row['late_hours']);
        $this->assertSame(666.67, $row['late_amount']);       // 2 × 333.3333
        $this->assertSame(1, $row['penalty_days']);
        $this->assertSame(200.0, $row['absence_amount']);     // 1 × 200
        $this->assertSame(866.67, $row['total_deduction']);
        $this->assertSame(5133.33, $row['net_salary']);
        $this->assertCount(2, $row['days']);                  // the late day + the absent day
    }
}
