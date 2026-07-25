<?php

namespace App\Console\Commands;

use App\Models\AttendanceMachine;
use App\Services\Attendance\ZktecoReadOnlySyncService;
use Illuminate\Console\Command;

/**
 * Incrementally reads attendance logs from every active device (read-only), on a
 * schedule. Any machine added later is picked up automatically — no per-device
 * configuration required — and each read only pulls punches newer than the last
 * sync. An unreachable device is reported and the loop continues.
 */
class SyncAttendanceDevices extends Command
{
    protected $signature = 'attendance:sync-devices {--device= : Sync only this device id}';

    protected $description = 'Read attendance logs incrementally from all active LAN devices without changing device state';

    public function handle(ZktecoReadOnlySyncService $service): int
    {
        $devices = AttendanceMachine::query()
            ->where('is_active', true)
            ->when($this->option('device'), fn ($q) => $q->whereKey($this->option('device')))
            ->get();

        foreach ($devices as $device) {
            try {
                $batch = $service->sync($device);
                $this->info("{$device->device_name}: {$batch->imported_count} imported");
            } catch (\Throwable $e) {
                $this->error("{$device->device_name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
