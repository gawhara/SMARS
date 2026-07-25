<?php

namespace Tests\Feature;

use App\Models\AttendanceMachine;
use App\Models\AttendanceSyncBatch;
use App\Services\Attendance\ZktecoReadOnlySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceSyncScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function device(string $name, bool $active): AttendanceMachine
    {
        return AttendanceMachine::create([
            'device_name' => $name,
            'connection_type' => 'lan',
            'ip_address' => '192.168.1.'.random_int(2, 250),
            'port' => 4370,
            'is_active' => $active,
        ]);
    }

    public function test_command_syncs_every_active_device_including_new_ones(): void
    {
        $fake = new RecordingSync();
        $this->app->instance(ZktecoReadOnlySyncService::class, $fake);

        $first = $this->device('Gate A', true);
        $addedLater = $this->device('Gate B', true); // a machine added after setup
        $this->device('Retired', false);             // must be skipped

        $this->artisan('attendance:sync-devices')->assertSuccessful();

        $this->assertEqualsCanonicalizing([$first->id, $addedLater->id], $fake->synced);
    }

    public function test_command_can_target_a_single_device(): void
    {
        $fake = new RecordingSync();
        $this->app->instance(ZktecoReadOnlySyncService::class, $fake);

        $this->device('Gate A', true);
        $only = $this->device('Gate B', true);

        $this->artisan('attendance:sync-devices', ['--device' => $only->id])->assertSuccessful();

        $this->assertSame([$only->id], $fake->synced);
    }
}

/**
 * Records which devices the scheduled command asked to sync, without hardware.
 */
class RecordingSync extends ZktecoReadOnlySyncService
{
    /** @var array<int, int> */
    public array $synced = [];

    public function __construct()
    {
        // No hardware dependencies for the fake.
    }

    public function sync(AttendanceMachine $device, ?int $userId = null): AttendanceSyncBatch
    {
        $this->synced[] = $device->id;

        return new AttendanceSyncBatch(['imported_count' => 0]);
    }
}
