<?php

namespace Tests\Feature;

use App\Models\AttendanceMachine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricDeviceTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    public function test_index_page_loads(): void
    {
        $this->actingAs($this->actingUser())->get(route('devices.index'))->assertOk();
    }

    public function test_can_create_lan_device_with_encrypted_password(): void
    {
        $user = $this->actingUser();

        $response = $this->actingAs($user)->post(route('devices.store'), [
            'device_name' => 'Main Gate',
            'device_model' => 'ZKTeco MB1000',
            'connection_type' => 'lan',
            'ip_address' => '192.168.1.50',
            'port' => 4370,
            'password' => 'secret-pass',
            'is_active' => '1',
        ]);

        $device = AttendanceMachine::firstOrFail();
        $response->assertRedirect(route('devices.show', $device));

        $this->assertSame('192.168.1.50', $device->ip_address);
        // Password is stored encrypted (not in plaintext) but decrypts via the cast.
        $this->assertNotSame('secret-pass', $device->getRawOriginal('password'));
        $this->assertSame('secret-pass', $device->password);
        $this->assertSame($user->id, $device->created_by);
    }

    public function test_ddns_connection_requires_domain(): void
    {
        $this->actingAs($this->actingUser())->post(route('devices.store'), [
            'device_name' => 'Remote',
            'connection_type' => 'ddns',
            'port' => 4370,
        ])->assertSessionHasErrors('domain');

        $this->assertSame(0, AttendanceMachine::count());
    }

    public function test_lan_connection_requires_ip(): void
    {
        $this->actingAs($this->actingUser())->post(route('devices.store'), [
            'device_name' => 'No IP',
            'connection_type' => 'lan',
            'port' => 4370,
        ])->assertSessionHasErrors('ip_address');
    }

    public function test_test_connection_records_unreachable(): void
    {
        $user = $this->actingUser();
        // Port 1 on localhost has nothing listening -> connection refused (fast).
        $device = AttendanceMachine::create([
            'device_name' => 'Dead',
            'connection_type' => 'lan',
            'ip_address' => '127.0.0.1',
            'port' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('devices.test', $device))->assertRedirect();

        $device->refresh();
        $this->assertSame('unreachable', $device->status);
        $this->assertNotNull($device->last_failed_connection_at);
    }
}
