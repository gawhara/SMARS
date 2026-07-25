<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceEmployeeTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $code): Employee
    {
        $company = Company::firstOrCreate(['code' => 'C1'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'اسم '.$code, 'name_en' => 'Emp '.$code,
            'employee_code' => $code, 'hr_employee_id' => 'HR-'.$code,
            'national_id' => '1'.substr(str_pad((string) crc32($code), 9, '0'), 0, 9),
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi', 'passport_id' => 'P-'.$code,
            'status' => 'active', 'start_date' => '2026-07-01',
        ]);
    }

    public function test_directory_lists_employees_with_attendance_links(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee('EMP-1');

        $this->actingAs($user)->get(route('attendance.index'))
            ->assertOk()
            ->assertSee($employee->localizedName())
            ->assertSee(route('attendance.employee', $employee));
    }

    public function test_employee_attendance_page_shows_dashboard_and_days(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee('EMP-1');

        foreach ([['08:00', 'in'], ['17:00', 'out']] as [$time, $type]) {
            $this->actingAs($user)->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'punch_at' => "2026-07-12T{$time}",
                'punch_type' => $type,
            ]);
        }

        $this->actingAs($user)->get(route('attendance.employee', [
            'employee' => $employee->id,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]))
            ->assertOk()
            ->assertSee(__('app.att.absent_days'))
            ->assertSee(__('app.att.present_days'))
            ->assertSee('2026-07-12');
    }

    public function test_printable_monthly_report_lists_every_day(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee('EMP-1');

        foreach ([['08:00', 'in'], ['17:00', 'out']] as [$time, $type]) {
            $this->actingAs($user)->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'punch_at' => "2026-07-12T{$time}",
                'punch_type' => $type,
            ]);
        }

        $response = $this->actingAs($user)->get(route('attendance.employee.print', [
            'employee' => $employee->id,
            'month' => '2026-07',
        ]));

        $response->assertOk()
            ->assertSee(__('app.att.monthly_report_title'))
            ->assertSee('2026-07-12')   // the worked day
            ->assertSee('2026-07-31');  // last day of the month is still listed
    }
}
