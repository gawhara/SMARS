<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\Attendance\AttendanceMatrixService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $company = Company::firstOrCreate(['code' => 'CO1'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'اسم', 'name_en' => 'Name',
            'employee_code' => 'EMP-1', 'hr_employee_id' => 'HR-1',
            'national_id' => '1234567890', 'nationality' => 'SA', 'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P1', 'status' => 'active',
        ]);
    }

    public function test_matrix_derives_present_late_absent_and_rest(): void
    {
        $employee = $this->employee();

        // A month firmly in the past so unpunched workdays count as absent.
        $month = Carbon::create(2026, 6, 1);

        // Mon 2026-06-01: on time (08:00) -> present
        AttendanceRecord::create(['employee_id' => $employee->id, 'punch_at' => '2026-06-01 08:00:00', 'punch_type' => 'in']);
        // Tue 2026-06-02: late (08:30 > 08:15) -> late
        AttendanceRecord::create(['employee_id' => $employee->id, 'punch_at' => '2026-06-02 08:30:00', 'punch_type' => 'in']);
        // Wed 2026-06-03: no punch -> absent

        $matrix = app(AttendanceMatrixService::class)->build(
            $month,
            collect([$employee]),
            AttendanceRecord::all(),
        );

        $days = $matrix[$employee->id]['days'];
        $this->assertSame('present', $days[1]);   // Mon
        $this->assertSame('late', $days[2]);      // Tue
        $this->assertSame('absent', $days[3]);    // Wed
        $this->assertSame('rest', $days[5]);      // 2026-06-05 is a Friday

        $summary = $matrix[$employee->id]['summary'];
        $this->assertSame(1, $summary['present']);
        $this->assertSame(1, $summary['late']);
    }

    public function test_matrix_page_loads(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('attendance.matrix', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee(__('app.att.legend'));
    }
}
