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
        // Incremental: ask the device script to return only punches at/after the
        // floor (last sync, or the minimum date on a first sync), so repeat syncs
        // transfer a tiny payload instead of the whole history.
        $floor = $this->syncFloor($device);

        $args = [
            \App\Support\PythonInterpreter::resolve(),
            base_path('scripts/zkteco_readonly_sync.py'),
            '--ip', (string) $device->host(),
            '--port', (string) $device->port,
            '--comm-key', (string) $device->comm_key,
            '--timeout', '8',
        ];
        if ($floor) {
            $args[] = '--since';
            $args[] = $floor->format('Y-m-d H:i:s');
        }

        $process = new Process($args, null, \App\Support\PythonInterpreter::processEnv());
        $process->setTimeout(240); // a full device read of a busy device can take minutes
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
        $floor = $this->syncFloor($device);

        $batch = DB::transaction(function () use ($rows, $device, $userId, $floor, $until) {
            $batch = AttendanceSyncBatch::create([
                'source' => 'device',
                'attendance_machine_id' => $device->id,
                'total_rows' => count($rows),
                'created_by' => $userId,
            ]);

            $imported = $matched = $unmatched = $duplicate = 0;

            // Preload existing punch signatures for this device+window in one query
            // so dedup is an in-memory lookup, and cache employee resolution so a
            // large import hits the employees table once per user, not once per row.
            $seen = [];
            AttendanceRecord::where('attendance_machine_id', $device->id)
                ->when($floor, fn ($q) => $q->where('punch_at', '>=', $floor))
                ->where('punch_at', '<=', $until)
                ->select('device_user_id', 'punch_at')
                ->cursor()
                ->each(function ($record) use (&$seen): void {
                    $seen[$record->device_user_id.'|'.$record->punch_at->format('Y-m-d H:i:s')] = true;
                });

            $employeeCache = [];

            foreach ($rows as $row) {
                // Skip garbage rows the device sometimes reports (blank user id).
                $deviceUserId = trim((string) ($row['device_user_id'] ?? ''));
                if ($deviceUserId === '') {
                    continue;
                }

                $at = Carbon::parse($row['punch_at']);

                // Keep only punches within [floor .. now]; drop older/garbage/future.
                if ($at->gt($until) || ($floor && $at->lt($floor))) {
                    continue;
                }

                $key = $deviceUserId.'|'.$at->format('Y-m-d H:i:s');
                if (isset($seen[$key])) {
                    $duplicate++;

                    continue;
                }

                if (! array_key_exists($deviceUserId, $employeeCache)) {
                    $employeeCache[$deviceUserId] = $this->attendance->resolveEmployee($deviceUserId);
                }
                $employee = $employeeCache[$deviceUserId];

                AttendanceRecord::create([
                    'employee_id' => $employee?->id,
                    'attendance_machine_id' => $device->id,
                    'device_user_id' => $deviceUserId,
                    'punch_at' => $at,
                    'punch_type' => $this->attendance->normalizePunchType($row['punch_type']),
                    'raw_punch_type' => $row['punch_type'],
                    'verification_type' => $row['verification_type'] ?? null,
                    'source' => 'device',
                    'company_id' => $employee?->company_id,
                    'branch_id' => $employee?->branch_id,
                    'sync_batch_id' => $batch->id,
                ]);

                $seen[$key] = true;
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

        $batchRecords = AttendanceRecord::where('sync_batch_id', $batch->id)->get();
        // Only matched punches need a daily summary (rebuildForRecords ignores the rest).
        $this->summaries->rebuildForRecords($batchRecords);

        // Advance the high-water mark to the newest punch imported — matched OR
        // unmatched — so the next sync's window starts after everything already
        // pulled and never re-fetches unmatched punches. A run that imports nothing
        // leaves the mark untouched.
        $newMax = $batchRecords->max('punch_at');
        $lastAttendance = ($newMax && (! $since || $newMax->gt($since))) ? $newMax : $device->last_attendance_at;

        $device->forceFill([
            'status' => 'online',
            'last_sync_at' => now(),
            'last_successful_connection_at' => now(),
            'last_attendance_at' => $lastAttendance,
        ])->save();

        return $batch;
    }

    /**
     * The incremental lower bound — always the latest of:
     *   - the last punch already stored for this device (self-correcting, so the
     *     sync stays incremental even if the cached mark is missing/stale),
     *   - the device's cached last-synced punch,
     *   - the configured minimum date (first-ever sync only).
     *
     * Because it is never below the last stored punch, the sync always pulls only
     * what is new and never re-imports the whole history.
     */
    private function syncFloor(AttendanceMachine $device): ?Carbon
    {
        $candidates = collect();

        $lastStored = AttendanceRecord::where('attendance_machine_id', $device->id)->max('punch_at');
        if ($lastStored) {
            $candidates->push(Carbon::parse($lastStored));
        }

        if ($device->last_attendance_at) {
            $candidates->push(Carbon::parse($device->last_attendance_at));
        }

        if (config('services.zkteco.min_punch_date')) {
            $candidates->push(Carbon::parse(config('services.zkteco.min_punch_date'))->startOfDay());
        }

        return $candidates->isEmpty() ? null : $candidates->max();
    }
}
