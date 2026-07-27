<?php

namespace Tests\Feature;

use App\Models\AttendanceHoliday;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\Attendance\PayrollDeductionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollDeductionTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): PayrollDeductionService
    {
        return app(PayrollDeductionService::class);
    }

    private function employee(array $overrides = []): Employee
    {
        $company = Company::firstOrCreate(['code' => 'PAY'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create(array_merge([
            'company_id' => $company->id, 'name_ar' => 'م', 'name_en' => 'Emp',
            'employee_code' => 'PAY-1', 'hr_employee_id' => 'HR-PAY-1', 'national_id' => '1234567896',
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'PAY-P', 'status' => 'active',
            'basic_salary_gosi' => 5000, 'housing_allowance_gosi' => 1000,
        ], $overrides));
    }

    private function assignShifts(Employee $employee, array $shifts): void
    {
        $schedule = (string) Str::uuid();
        $first = null;
        foreach ($shifts as $i => [$start, $end]) {
            $s = Shift::create(['schedule_id' => $schedule, 'shift_number' => $i + 1, 'schedule_name_ar' => 'ج', 'name_ar' => 'ش'.$i.'-'.$schedule, 'name_en' => 'S'.$i.'-'.$schedule, 'start_time' => $start, 'end_time' => $end, 'is_active' => true]);
            $first ??= $s;
        }
        $employee->update(['shift_id' => $first->id]);
    }

    public function test_gosi_salary_basis(): void
    {
        $this->assertSame(6000.0, $this->svc()->salaryBasis($this->employee()));
    }

    public function test_daily_shift_minutes_one_and_two_shifts(): void
    {
        $one = $this->employee();
        $this->assignShifts($one, [['08:00', '17:00']]);
        $this->assertSame(540, $this->svc()->dailyShiftMinutes($one->fresh()));

        $two = $this->employee(['employee_code' => 'PAY-2', 'hr_employee_id' => 'HR-PAY-2', 'national_id' => '1234567897', 'passport_id' => 'PAY-P2']);
        $this->assignShifts($two, [['08:00', '12:00'], ['16:00', '20:00']]);
        $this->assertSame(480, $this->svc()->dailyShiftMinutes($two->fresh()));
    }

    public function test_hourly_and_daily_rates_and_amounts(): void
    {
        $svc = $this->svc();
        // 6000 salary over 200 scheduled hours = 30/hr.
        $this->assertSame(30.0, $svc->hourlyRate(6000, 200 * 60));
        // Default divisor is 30 → 6000/30 = 200/day.
        $this->assertSame(200.0, $svc->dailyRate(6000));
        // 2 deduction hours × 30 = 60.
        $this->assertSame(60.0, $svc->hourAmount(2, 30.0));
        // 1 penalty day × 200 = 200.
        $this->assertSame(200.0, $svc->absenceAmount(1, 200.0));
    }

    public function test_monthly_scheduled_minutes_excludes_weekends_and_holidays(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '17:00']]); // 540 min/day
        $e = $e->fresh();

        $month = Carbon::parse('2026-07-01');

        // Independent count of non-Friday days in July 2026.
        $workingDays = 0;
        for ($d = $month->copy()->startOfMonth(); $d->lte($month->copy()->endOfMonth()); $d->addDay()) {
            if ($d->dayOfWeek !== Carbon::FRIDAY) {
                $workingDays++;
            }
        }

        $this->assertSame($workingDays * 540, $this->svc()->scheduledMinutes($e, $month->copy()->startOfMonth(), $month->copy()->endOfMonth(), collect(), collect()));

        // Add a holiday on a working day (2026-07-01 is a Wednesday) → one fewer day.
        $holiday = collect([AttendanceHoliday::create(['company_id' => null, 'name_ar' => 'ع', 'name_en' => 'H', 'holiday_date' => '2026-07-01', 'is_active' => true, 'is_paid' => true])]);
        $this->assertSame(($workingDays - 1) * 540, $this->svc()->scheduledMinutes($e, $month->copy()->startOfMonth(), $month->copy()->endOfMonth(), $holiday, collect()));
    }

    public function test_scheduled_minutes_respect_join_date(): void
    {
        $e = $this->employee(['start_date' => '2026-07-16']);
        $this->assignShifts($e, [['08:00', '17:00']]);
        $e = $e->fresh();

        $month = Carbon::parse('2026-07-01');
        $daysAfterJoin = 0;
        for ($d = Carbon::parse('2026-07-16'); $d->lte($month->copy()->endOfMonth()); $d->addDay()) {
            if ($d->dayOfWeek !== Carbon::FRIDAY) {
                $daysAfterJoin++;
            }
        }

        $this->assertSame($daysAfterJoin * 540, $this->svc()->scheduledMinutes($e, $month->copy()->startOfMonth(), $month->copy()->endOfMonth(), collect(), collect()));
    }
}
