<?php

namespace Database\Seeders;

use App\Models\AttendanceDailySummary;
use App\Models\AttendanceMachine;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSyncBatch;
use App\Models\Employee;
use App\Models\User;
use App\Services\Attendance\AttendanceDailySummaryService;
use App\Services\Attendance\AttendanceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MachinePunchSeeder extends Seeder
{
    public function run(): void
    {
        $sourcePath = base_path('attendance-punches-2026-07-12.json');

        // Large device-export fixture (~10 MB) kept out of the repo. Skip gracefully
        // when it is not present so the seed pipeline still succeeds.
        if (! is_file($sourcePath)) {
            $this->command?->warn('MachinePunchSeeder: attendance-punches-2026-07-12.json not found — skipping punch import.');

            return;
        }

        $payload = json_decode(file_get_contents($sourcePath), true, flags: JSON_THROW_ON_ERROR);
        $rows = collect($payload['records'] ?? [])->filter(fn (array $row) =>
            $row['punch_at'] >= '2026-05-01 00:00:00' && $row['punch_at'] < '2026-07-01 00:00:00'
        )->sortBy('punch_at')->values();

        $adminId = User::where('email', 'admin@smars.local')->value('id');
        $attendance = app(AttendanceService::class);

        $machine = AttendanceMachine::withTrashed()->updateOrCreate(
            ['serial_number' => 'CGDQ210260182'],
            [
                'deleted_at' => null,
                'device_name' => 'ZKTeco MB1000/ID - LAN',
                'device_model' => 'ZKTeco MB1000/ID',
                'connection_type' => 'lan',
                'ip_address' => '192.168.10.24',
                'port' => 4370,
                'comm_key' => 0,
                'company_id' => null,
                'branch_id' => null,
                'location_description' => 'Global attendance device',
                'timezone' => 'Asia/Riyadh',
                'status' => 'online',
                'is_active' => true,
                'automatic_sync_enabled' => false,
                'sync_interval_minutes' => 5,
                'notes' => 'Read-only integration. Never alter device data or settings.',
            ]
        );

        $employees = Employee::query()->get()->keyBy(fn (Employee $employee) => (string) $employee->hr_employee_id);
        $now = now();

        DB::transaction(function () use ($rows, $machine, $employees, $attendance, $adminId, $now, $sourcePath): void {
            AttendanceDailySummary::query()->delete();
            DB::table('attendance_records')->delete();
            DB::table('attendance_sync_batches')->delete();

            $batch = AttendanceSyncBatch::create([
                'source' => 'import',
                'file_name' => basename($sourcePath),
                'attendance_machine_id' => $machine->id,
                'total_rows' => $rows->count(),
                'imported_count' => $rows->count(),
                'matched_count' => $rows->filter(fn (array $row) => $employees->has((string) $row['device_user_id']))->count(),
                'unmatched_count' => $rows->reject(fn (array $row) => $employees->has((string) $row['device_user_id']))->count(),
                'duplicate_count' => 0,
                'notes' => 'May and June 2026 punches imported from the read-only device download.',
                'created_by' => $adminId,
            ]);

            $records = $rows->map(function (array $row) use ($machine, $employees, $attendance, $batch, $adminId, $now): array {
                $employee = $employees->get((string) $row['device_user_id']);

                return [
                    'employee_id' => $employee?->id,
                    'attendance_machine_id' => $machine->id,
                    'device_user_id' => (string) $row['device_user_id'],
                    'punch_at' => Carbon::parse($row['punch_at'])->format('Y-m-d H:i:s'),
                    'punch_type' => $attendance->normalizePunchType($row['punch_type'] ?? null),
                    'raw_punch_type' => (string) ($row['punch_type'] ?? ''),
                    'verification_type' => (string) ($row['verification_type'] ?? ''),
                    'source' => 'import',
                    'company_id' => $employee?->company_id,
                    'branch_id' => $employee?->branch_id,
                    'sync_batch_id' => $batch->id,
                    'notes' => 'Imported from read-only ZKTeco download.',
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            });

            $records->chunk(500)->each(fn ($chunk) => AttendanceRecord::query()->insert($chunk->all()));
        });

        app(AttendanceDailySummaryService::class)->rebuildForRecords(
            AttendanceRecord::query()->where('sync_batch_id', AttendanceSyncBatch::latest('id')->value('id'))->get()
        );
    }
}
