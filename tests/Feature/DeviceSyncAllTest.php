<?php

namespace Tests\Feature;

use App\Models\AttendanceMachine;
use App\Models\AttendanceSyncBatch;
use App\Models\User;
use App\Services\Attendance\ZktecoReadOnlySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceSyncAllTest extends TestCase
{
    use RefreshDatabase;

    private function device(string $name, bool $active = true): AttendanceMachine
    {
        return AttendanceMachine::create([
            'device_name' => $name,
            'connection_type' => 'lan',
            'ip_address' => '192.168.1.'.random_int(2, 250),
            'port' => 4370,
            'is_active' => $active,
        ]);
    }

    public function test_read_all_aggregates_new_punches_across_active_devices(): void
    {
        $this->app->instance(ZktecoReadOnlySyncService::class, new FakeSync());

        $this->device('Gate A');
        $this->device('Gate B');
        $this->device('Retired', active: false); // must be skipped

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('devices.sync-all'))
            ->assertRedirect()
            ->assertSessionHas('status');

        // 2 active devices × 3 punches each = 6, reported in the flash message.
        $this->assertStringContainsString('6', session('status'));
    }

    public function test_read_all_reports_unreachable_devices_and_keeps_going(): void
    {
        $offline = $this->device('Broken');
        $this->device('Healthy');

        $fake = new FakeSync();
        $fake->offline = [$offline->id];
        $this->app->instance(ZktecoReadOnlySyncService::class, $fake);

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('devices.sync-all'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertStringContainsString('Broken', session('error'));
    }

    public function test_read_all_with_no_active_devices_errors(): void
    {
        $this->device('Off', active: false);

        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('devices.sync-all'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}

/**
 * Stand-in for the read-only sync service so the fleet loop can be tested without
 * real ZKTeco hardware. Returns a fixed new-punch count per device, or throws for
 * devices marked offline.
 */
class FakeSync extends ZktecoReadOnlySyncService
{
    /** @var array<int, int> */
    public array $offline = [];

    public int $perDevice = 3;

    public function __construct()
    {
        // Bypass the real service's dependencies — this fake performs no I/O.
    }

    public function sync(AttendanceMachine $device, ?int $userId = null): AttendanceSyncBatch
    {
        if (in_array($device->id, $this->offline, true)) {
            throw new \RuntimeException('device offline');
        }

        return new AttendanceSyncBatch(['imported_count' => $this->perDevice]);
    }
}
