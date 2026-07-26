<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Services\Attendance\AttendanceDeductionEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceDeductionEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    private const DAY = '2026-07-20'; // a Monday

    private function employee(): Employee
    {
        $company = Company::create(['name_ar' => 'ش', 'name_en' => 'Co', 'code' => 'DED', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id, 'name_ar' => 'موظف', 'name_en' => 'Emp',
            'employee_code' => 'DED-1', 'hr_employee_id' => 'HR-DED-1', 'national_id' => '1234567895',
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'DED-P', 'status' => 'active',
        ]);
    }

    /** @param array<int, array{0:string,1:string}> $shifts [start,end] */
    private function assignShifts(Employee $employee, array $shifts): void
    {
        $schedule = (string) Str::uuid();
        $first = null;
        foreach ($shifts as $i => [$start, $end]) {
            $shift = Shift::create([
                'schedule_id' => $schedule, 'shift_number' => $i + 1, 'schedule_name_ar' => 'ج',
                'name_ar' => 'ش'.$i, 'name_en' => 'S'.$i, 'start_time' => $start, 'end_time' => $end, 'is_active' => true,
            ]);
            $first ??= $shift;
        }
        $employee->update(['shift_id' => $first->id]);
    }

    private function punch(Employee $e, string $time, string $type): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id' => $e->id, 'device_user_id' => $e->hr_employee_id,
            'punch_at' => self::DAY.' '.$time, 'punch_type' => $type, 'source' => 'manual', 'company_id' => $e->company_id,
        ]);
    }

    private function evaluate(Employee $e, ?string $calendar = null): array
    {
        $punches = AttendanceRecord::where('employee_id', $e->id)->get();

        return app(AttendanceDeductionEvaluator::class)->evaluate($e, self::DAY, $punches, $calendar);
    }

    public function test_one_shift_on_time_has_no_deduction(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '17:00']]);
        $this->punch($e, '08:00', 'in');
        $this->punch($e, '17:00', 'out');

        $r = $this->evaluate($e);
        $this->assertSame(0, $r['deduction_hours']);
        $this->assertSame('present', $r['status']);
        $this->assertSame(0, $r['absence']['penalty_days']);
    }

    public function test_one_shift_late_thirty_minutes_is_two_hours(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '17:00']]);
        $this->punch($e, '08:30', 'in');
        $this->punch($e, '17:00', 'out');

        $r = $this->evaluate($e);
        $this->assertSame(2, $r['deduction_hours']);
        $this->assertSame('late', $r['status']);
    }

    public function test_late_and_early_are_added_separately(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '17:00']]);
        $this->punch($e, '08:30', 'in');   // 30 late  -> 2
        $this->punch($e, '16:30', 'out');  // 30 early -> 2

        $this->assertSame(4, $this->evaluate($e)['deduction_hours']);
    }

    public function test_missing_checkout_charges_three_hours_not_early(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '17:00']]);
        $this->punch($e, '08:00', 'in'); // on time, no check-out

        $r = $this->evaluate($e);
        $this->assertSame(3, $r['deduction_hours']); // missing punch only, no early
        $this->assertSame('incomplete', $r['status']);
        $this->assertTrue($r['needs_review']);
    }

    public function test_missing_checkin_charges_three_hours_not_late(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '17:00']]);
        $this->punch($e, '17:00', 'out'); // on-time out, no check-in

        $this->assertSame(3, $this->evaluate($e)['deduction_hours']);
    }

    public function test_no_punches_is_absence_not_missing_punch(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '17:00']]);

        $r = $this->evaluate($e);
        $this->assertSame(0, $r['deduction_hours']);        // no missing-punch charge (§18/25)
        $this->assertSame(1, $r['absence']['penalty_days']); // one-shift absence
        $this->assertSame('absent', $r['status']);
        $this->assertTrue($r['needs_review']);
    }

    public function test_two_shift_late_and_early_across_shifts(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '12:00'], ['16:00', '20:00']]);
        $this->punch($e, '08:30', 'in');   // shift 1 late 30 -> 2
        $this->punch($e, '12:00', 'out');
        $this->punch($e, '16:00', 'in');
        $this->punch($e, '19:30', 'out');  // shift 2 early 30 -> 2

        $r = $this->evaluate($e);
        $this->assertSame(4, $r['deduction_hours']);
        $this->assertSame(2, $r['required_shifts']);
        $this->assertSame(2, $r['attended_shifts']);
    }

    public function test_two_shift_missing_one_shift_is_half_day_absence(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '12:00'], ['16:00', '20:00']]);
        $this->punch($e, '08:00', 'in');  // shift 1 only
        $this->punch($e, '12:00', 'out');

        $r = $this->evaluate($e);
        $this->assertSame(0, $r['deduction_hours']);
        $this->assertSame(0.5, $r['absence']['absence_fraction']);
        $this->assertSame(1, $r['absence']['penalty_days']);
        $this->assertSame('partial', $r['status']);
    }

    public function test_rest_day_has_no_deduction(): void
    {
        $e = $this->employee();
        $this->assignShifts($e, [['08:00', '17:00']]);

        $r = $this->evaluate($e, 'rest');
        $this->assertSame(0, $r['deduction_hours']);
        $this->assertSame(0, $r['absence']['penalty_days']);
        $this->assertSame('rest', $r['status']);
    }
}
