<?php

namespace App\Services\Attendance;

use App\Models\AttendanceMachine;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSyncBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Strictly read-only ZKTeco attendance fetcher. Reads the device's punch log and
 * stores new punches, incrementally: only records dated from the device's last
 * successful sync up to now are considered, and duplicates are skipped.
 */
class ZktecoReadOnlySyncService
{
    public function __construct(
        private AttendanceService $attendance,
        private AttendanceDailySummaryService $summaries,
    ) {
    }

    public function sync(AttendanceMachine $device, ?int $userId = null): AttendanceSyncBatch
    {
        $process = new Process([
            config('services.zkteco.python'),
            base_path('scripts/zkteco_readonly_sync.py'),
            '--ip', (string) $device->host(),
            '--port', (string) $device->port,
            '--comm-key', (string) $device->comm_key,
            '--timeout', '8',
        ]);
        $process->setTimeout(30);
        $process->run();
        $data = json_decode($process->getOutput(), true);

        if (! $process->isSuccessful() || ! ($data['ok'] ?? false)) {
            $device->forceFill(['status' => 'sync_failed', 'last_failed_connection_at' => now()])->save();

            throw new \RuntimeException($data['error'] ?? $process->getErrorOutput() ?: 'Device sync failed');
        }

        return $this->importRecords($device, $data['records'] ?? [], $userId);
    }

    /**
     * Store the given device rows, keeping only those dated within the incremental
     * window [last successful sync → now] and skipping duplicates. Kept separate
     * from sync() so it can be exercised without real hardware.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function importRecords(AttendanceMachine $device, array $rows, ?int $userId = null): AttendanceSyncBatch
    {
        $since = $device->last_attendance_at ? Carbon::parse($device->last_attendance_at) : null;
        $until = now();

        $batch = DB::transaction(function () use ($rows, $device, $userId, $since, $until) {
            $batch = AttendanceSyncBatch::create([
                'source' => 'device',
                'attendance_machine_id' => $device->id,
                'total_rows' => count($rows),
                'created_by' => $userId,
            ]);

            $imported = $matched = $unmatched = $duplicate = 0;

            foreach ($rows as $row) {
                $at = Carbon::parse($row['punch_at']);

                // Incremental window: skip anything before the last sync or in the future.
                if ($at->gt($until) || ($since && $at->lt($since))) {
                    continue;
                }

                $exists = AttendanceRecord::where('attendance_machine_id', $device->id)
                    ->where('device_user_id', $row['device_user_id'])
                    ->where('punch_at', $at)
                    ->exists();

                if ($exists) {
                    $duplicate++;

                    continue;
                }

                $employee = $this->attendance->resolveEmployee($row['device_user_id']);

                AttendanceRecord::create([
                    'employee_id' => $employee?->id,
                    'attendance_machine_id' => $device->id,
                    'device_user_id' => $row['device_user_id'],
                    'punch_at' => $at,
                    'punch_type' => $this->attendance->normalizePunchType($row['punch_type']),
                    'raw_punch_type' => $row['punch_type'],
                    'verification_type' => $row['verification_type'] ?? null,
                    'source' => 'device',
                    'company_id' => $employee?->company_id,
                    'branch_id' => $employee?->branch_id,
                    'sync_batch_id' => $batch->id,
                ]);

                $imported++;
                $employee ? $matched++ : $unmatched++;
            }

            $batch->update([
                'imported_count' => $imported,
                'matched_count' => $matched,
                'unmatched_count' => $unmatched,
                'duplicate_count' => $duplicate,
            ]);

            return $batch;
        });

        $records = AttendanceRecord::where('sync_batch_id', $batch->id)->matched()->get();
        $this->summaries->rebuildForRecords($records);

        // Advance the high-water mark only when newer punches arrived, so a run that
        // imports nothing never clears it (which would re-widen the next window).
        $newMax = $records->max('punch_at');
        $lastAttendance = ($newMax && (! $since || $newMax->gt($since))) ? $newMax : $device->last_attendance_at;

        $device->forceFill([
            'status' => 'online',
            'last_sync_at' => now(),
            'last_successful_connection_at' => now(),
            'last_attendance_at' => $lastAttendance,
        ])->save();

        return $batch;
    }
}
