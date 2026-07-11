<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $company = Company::firstOrCreate(['code' => 'CO1'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'اسم', 'name_en' => 'Report Name',
            'employee_code' => 'EMP-9', 'hr_employee_id' => 'HR-9',
            'national_id' => '1234567899', 'nationality' => 'SA', 'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P9', 'status' => 'active',
        ]);
    }

    private function seedPunches(Employee $employee): void
    {
        // Mon 2026-06-01: 08:00 in, 17:00 out -> present, 9h
        AttendanceRecord::create(['employee_id' => $employee->id, 'punch_at' => '2026-06-01 08:00:00', 'punch_type' => 'in']);
        AttendanceRecord::create(['employee_id' => $employee->id, 'punch_at' => '2026-06-01 17:00:00', 'punch_type' => 'out']);
        // Tue 2026-06-02: 09:00 in -> late
        AttendanceRecord::create(['employee_id' => $employee->id, 'punch_at' => '2026-06-02 09:00:00', 'punch_type' => 'in']);
        // Wed 2026-06-03: no punch -> absent
    }

    public function test_report_aggregates_days_and_hours(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee();
        $this->seedPunches($employee);

        $response = $this->actingAs($user)->get(route('attendance.report', [
            'date_from' => '2026-06-01', 'date_to' => '2026-06-03',
        ]));

        $response->assertOk()->assertSee('EMP-9');

        $rows = $response->viewData('rows');
        $row = collect($rows)->firstWhere('employee.id', $employee->id);
        $this->assertSame(1, $row['present']);
        $this->assertSame(1, $row['late']);
        $this->assertSame(1, $row['absent']);
        $this->assertSame(9.0, $row['hours']);
        $this->assertSame(2, $row['worked_days']);
    }

    public function test_csv_export_streams_a_download(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee();
        $this->seedPunches($employee);

        $response = $this->actingAs($user)->get(route('attendance.report', [
            'date_from' => '2026-06-01', 'date_to' => '2026-06-03', 'export' => 'csv',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Report Name', $content);
        $this->assertStringContainsString('EMP-9', $content);
    }
}
