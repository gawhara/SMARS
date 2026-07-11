<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $code = 'EMP-0001'): Employee
    {
        $company = Company::firstOrCreate(['code' => 'CO1'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'اسم', 'name_en' => 'Name',
            'employee_code' => $code, 'hr_employee_id' => 'HR-'.$code,
            'national_id' => '1'.substr(str_pad((string) crc32($code), 9, '0'), 0, 9),
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P-'.$code, 'status' => 'active',
        ]);
    }

    public function test_index_page_loads(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('attendance.index'))->assertOk();
    }

    public function test_manual_punch_snapshots_employee_company(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $employee = $this->employee();

        $this->actingAs($user)->post(route('attendance.store'), [
            'employee_id' => $employee->id,
            'punch_at' => '2026-07-11T08:00',
            'punch_type' => 'in',
        ])->assertRedirect(route('attendance.index'));

        $record = AttendanceRecord::firstOrFail();
        $this->assertSame($employee->id, $record->employee_id);
        $this->assertSame($employee->company_id, $record->company_id);
        $this->assertSame('manual', $record->source);
        $this->assertTrue($record->isMatched());
    }

    public function test_csv_import_matches_dedupes_and_flags_unmatched(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->employee('EMP-0001');

        $csv = implode("\n", [
            'device_user_id,punch_at,punch_type,verification_type',
            'EMP-0001,2026-07-11 08:00:00,in,fingerprint',
            'UNKNOWN9,2026-07-11 08:05:00,in,fingerprint',
            'EMP-0001,2026-07-11 08:00:00,in,fingerprint', // duplicate of row 1
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('punches.csv', $csv);

        $this->actingAs($user)->post(route('attendance.import'), ['file' => $file])
            ->assertRedirect();

        // 2 imported (1 matched + 1 unmatched); 1 duplicate skipped.
        $this->assertSame(2, AttendanceRecord::count());
        $this->assertSame(1, AttendanceRecord::matched()->count());
        $this->assertSame(1, AttendanceRecord::unmatched()->count());
        $this->assertDatabaseHas('attendance_sync_batches', [
            'imported_count' => 2, 'matched_count' => 1, 'unmatched_count' => 1, 'duplicate_count' => 1,
        ]);
    }
}
