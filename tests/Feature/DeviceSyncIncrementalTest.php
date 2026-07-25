<?php

namespace Tests\Feature;

use App\Models\AttendanceMachine;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Attendance\ZktecoReadOnlySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceSyncIncrementalTest extends TestCase
{
    use RefreshDatabase;

    private function device(?string $lastAttendanceAt): AttendanceMachine
    {
        $device = AttendanceMachine::create([
            'device_name' => 'Gate',
            'connection_type' => 'lan',
            'ip_address' => '192.168.1.10',
            'port' => 4370,
            'is_active' => true,
        ]);

        // last_attendance_at is guarded (set via forceFill in the real sync).
        if ($lastAttendanceAt) {
            $device->forceFill(['last_attendance_at' => $lastAttendanceAt])->save();
        }

        return $device->refresh();
    }

    private function employee(): Employee
    {
        $company = Company::firstOrCreate(['code' => 'C1'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'اسم', 'name_en' => 'Emp', 'employee_code' => 'E1', 'hr_employee_id' => 'HR-1',
            'national_id' => '1234567890', 'nationality' => 'SA', 'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P1', 'status' => 'active',
        ]);
    }

    private function row(string $punchAt): array
    {
        return ['device_user_id' => 'HR-1', 'punch_at' => $punchAt, 'punch_type' => '0', 'verification_type' => '1'];
    }

    private function service(): ZktecoReadOnlySyncService
    {
        return app(ZktecoReadOnlySyncService::class);
    }

    public function test_only_records_from_last_sync_up_to_now_are_imported(): void
    {
        $this->employee();
        $device = $this->device(now()->subDays(5)->toDateTimeString());

        $batch = $this->service()->importRecords($device, [
            $this->row(now()->subDays(10)->toDateTimeString()), // before last sync -> skipped
            $this->row(now()->subDays(2)->toDateTimeString()),  // in window -> imported
            $this->row(now()->addDays(3)->toDateTimeString()),  // future -> skipped
        ]);

        $this->assertSame(1, $batch->imported_count);
        $this->assertSame(1, AttendanceRecord::count());
        $this->assertSame(
            now()->subDays(2)->toDateString(),
            AttendanceRecord::first()->punch_at->toDateString(),
        );
    }

    public function test_high_water_mark_advances_to_newest_imported_punch(): void
    {
        $this->employee();
        $device = $this->device(now()->subDays(5)->toDateTimeString());

        $this->service()->importRecords($device, [
            $this->row(now()->subDays(3)->toDateTimeString()),
            $this->row(now()->subDay()->toDateTimeString()),
        ]);

        $this->assertSame(
            now()->subDay()->toDateString(),
            $device->fresh()->last_attendance_at->toDateString(),
        );
    }

    public function test_stays_incremental_from_stored_punches_when_mark_is_missing(): void
    {
        $employee = $this->employee();
        // Device has NO cached high-water mark, but already holds a stored punch.
        $device = $this->device(null);
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_machine_id' => $device->id,
            'device_user_id' => 'HR-1',
            'punch_at' => now()->subDays(2),
            'punch_type' => 'in',
            'source' => 'device',
        ]);

        $batch = $this->service()->importRecords($device, [
            $this->row(now()->subDays(10)->toDateTimeString()), // older than the stored punch -> skipped
            $this->row(now()->subDay()->toDateTimeString()),    // newer -> imported
        ]);

        // Floor came from the stored punch, not the min-date, so it did not
        // re-import the older row.
        $this->assertSame(1, $batch->imported_count);
    }

    public function test_high_water_mark_advances_even_when_newest_punch_is_unmatched(): void
    {
        $this->employee(); // HR-1 exists (matched)
        $device = $this->device(now()->subDays(5)->toDateTimeString());

        $this->service()->importRecords($device, [
            $this->row(now()->subDays(3)->toDateTimeString()), // matched, older
            // Newest punch belongs to a device user with no employee record.
            ['device_user_id' => 'GHOST-9', 'punch_at' => now()->subDay()->toDateTimeString(), 'punch_type' => '0', 'verification_type' => '1'],
        ]);

        // The mark must advance to the unmatched newest punch, so the next sync
        // does not re-fetch it every time.
        $this->assertSame(
            now()->subDay()->toDateString(),
            $device->fresh()->last_attendance_at->toDateString(),
        );
    }

    public function test_duplicate_punches_are_skipped_on_reimport(): void
    {
        $this->employee();
        $device = $this->device(now()->subDays(5)->toDateTimeString());
        $when = now()->subDays(2)->toDateTimeString();

        $this->service()->importRecords($device, [$this->row($when)]);
        $batch = $this->service()->importRecords($device, [$this->row($when)]);

        $this->assertSame(0, $batch->imported_count);
        $this->assertSame(1, $batch->duplicate_count);
        $this->assertSame(1, AttendanceRecord::count());
    }

    public function test_first_sync_imports_all_past_records_up_to_now(): void
    {
        $this->employee();
        $device = $this->device(null); // never synced

        $batch = $this->service()->importRecords($device, [
            $this->row(now()->subDays(30)->toDateTimeString()),
            $this->row(now()->subDays(1)->toDateTimeString()),
            $this->row(now()->addDays(2)->toDateTimeString()), // future still excluded
        ]);

        $this->assertSame(2, $batch->imported_count);
    }
}
