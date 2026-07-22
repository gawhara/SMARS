<?php

namespace Tests\Feature;

use App\Models\AttendanceMachine;
use App\Models\Company;
use App\Models\DeviceEnrollment;
use App\Models\Employee;
use App\Models\User;
use App\Services\Biometric\ZktecoDeviceGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private FakeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new FakeGateway();
        $this->app->instance(ZktecoDeviceGateway::class, $this->gateway);
    }

    private function device(string $name): AttendanceMachine
    {
        return AttendanceMachine::create([
            'device_name' => $name,
            'connection_type' => 'lan',
            'ip_address' => '192.168.1.'.random_int(2, 250),
            'port' => 4370,
            'is_active' => true,
        ]);
    }

    private function employee(string $code): Employee
    {
        $company = Company::firstOrCreate(['code' => 'CO1'], ['name_ar' => 'ش', 'name_en' => 'Co', 'is_active' => true]);

        return Employee::create([
            'company_id' => $company->id,
            'name_ar' => 'اسم', 'name_en' => 'Name '.$code,
            'employee_code' => $code, 'hr_employee_id' => 'HR-'.$code,
            'national_id' => '1'.substr(str_pad((string) crc32($code), 9, '0'), 0, 9),
            'nationality' => 'SA', 'saudi_non_saudi' => 'saudi',
            'passport_id' => 'P-'.$code, 'status' => 'active',
        ]);
    }

    private function seedOnDevice(AttendanceMachine $device, Employee ...$employees): void
    {
        foreach ($employees as $employee) {
            $this->gateway->users[$device->id][$employee->hr_employee_id] = [
                'user_id' => $employee->hr_employee_id,
                'name' => $employee->name_en,
                'privilege' => 0,
                'fingers' => [['fid' => 0, 'valid' => 1, 'template' => 'AAA=']],
            ];
        }
    }

    public function test_copy_writes_to_target_and_records_enrollment_keeping_source(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $source = $this->device('Source');
        $target = $this->device('Target');
        $e1 = $this->employee('EMP-1');
        $this->seedOnDevice($source, $e1);

        $this->actingAs($user)->post(route('devices.provision.copy', $source), [
            'employee_ids' => [$e1->id],
            'target' => $target->id,
        ])->assertRedirect(route('devices.provision', $source))->assertSessionHas('status');

        // Written to the target hardware and recorded as an enrollment there.
        $this->assertArrayHasKey($e1->hr_employee_id, $this->gateway->users[$target->id]);
        $this->assertDatabaseHas('device_enrollments', [
            'attendance_machine_id' => $target->id, 'employee_id' => $e1->id, 'source_machine_id' => $source->id,
        ]);
        // Source is left intact on a copy.
        $this->assertArrayHasKey($e1->hr_employee_id, $this->gateway->users[$source->id]);
    }

    public function test_move_clears_source_after_successful_copy(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $source = $this->device('Source');
        $target = $this->device('Target');
        $e1 = $this->employee('EMP-1');
        $this->seedOnDevice($source, $e1);
        DeviceEnrollment::create(['attendance_machine_id' => $source->id, 'employee_id' => $e1->id, 'device_user_id' => $e1->hr_employee_id, 'enrolled_at' => now()]);

        $this->actingAs($user)->post(route('devices.provision.move', $source), [
            'employee_ids' => [$e1->id],
            'target' => $target->id,
        ])->assertRedirect()->assertSessionHas('status');

        // Present on target, gone from source (both hardware and enrollment record).
        $this->assertArrayHasKey($e1->hr_employee_id, $this->gateway->users[$target->id]);
        $this->assertArrayNotHasKey($e1->hr_employee_id, $this->gateway->users[$source->id]);
        $this->assertDatabaseMissing('device_enrollments', ['attendance_machine_id' => $source->id, 'employee_id' => $e1->id]);
        $this->assertDatabaseHas('device_enrollments', ['attendance_machine_id' => $target->id, 'employee_id' => $e1->id]);
    }

    public function test_move_leaves_source_untouched_when_target_write_fails(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $source = $this->device('Source');
        $target = $this->device('Target');
        $e1 = $this->employee('EMP-1');
        $this->seedOnDevice($source, $e1);
        DeviceEnrollment::create(['attendance_machine_id' => $source->id, 'employee_id' => $e1->id, 'device_user_id' => $e1->hr_employee_id, 'enrolled_at' => now()]);
        $this->gateway->writeFails = true;

        $this->actingAs($user)->post(route('devices.provision.move', $source), [
            'employee_ids' => [$e1->id],
            'target' => $target->id,
        ])->assertRedirect()->assertSessionHas('error');

        // Safety: nothing removed from the source when the push failed.
        $this->assertArrayHasKey($e1->hr_employee_id, $this->gateway->users[$source->id]);
        $this->assertDatabaseHas('device_enrollments', ['attendance_machine_id' => $source->id, 'employee_id' => $e1->id]);
    }

    public function test_delete_removes_users_from_device_and_enrollments(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $device = $this->device('Gate');
        $e1 = $this->employee('EMP-1');
        $this->seedOnDevice($device, $e1);
        DeviceEnrollment::create(['attendance_machine_id' => $device->id, 'employee_id' => $e1->id, 'device_user_id' => $e1->hr_employee_id, 'enrolled_at' => now()]);

        $this->actingAs($user)->post(route('devices.provision.delete', $device), [
            'employee_ids' => [$e1->id],
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertArrayNotHasKey($e1->hr_employee_id, $this->gateway->users[$device->id]);
        $this->assertDatabaseMissing('device_enrollments', ['attendance_machine_id' => $device->id, 'employee_id' => $e1->id]);
    }

    public function test_provisioning_page_renders(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $device = $this->device('Gate');
        $e1 = $this->employee('EMP-1');
        DeviceEnrollment::create(['attendance_machine_id' => $device->id, 'employee_id' => $e1->id, 'device_user_id' => $e1->hr_employee_id, 'enrolled_at' => now()]);

        $this->actingAs($user)->get(route('devices.provision', $device))
            ->assertOk()
            ->assertSee(__('app.provision.title'))
            ->assertSee($e1->localizedName());
    }

    public function test_copy_requires_employee_selection(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $source = $this->device('Source');
        $this->device('Target');

        $this->actingAs($user)->post(route('devices.provision.copy', $source), [
            'target' => 'all',
        ])->assertSessionHasErrors('employee_ids');
    }
}

/**
 * In-memory stand-in for the device gateway so provisioning logic can be tested
 * without touching physical hardware. Keyed by device id → [user_id => user].
 */
class FakeGateway extends ZktecoDeviceGateway
{
    /** @var array<int, array<string, array>> */
    public array $users = [];

    public bool $writeFails = false;

    public function read(AttendanceMachine $device, array $deviceUserIds): array
    {
        $found = [];
        foreach ($this->users[$device->id] ?? [] as $userId => $user) {
            if (in_array((string) $userId, $deviceUserIds, true)) {
                $found[] = $user;
            }
        }

        return ['ok' => true, 'users' => $found];
    }

    public function write(AttendanceMachine $device, array $users): array
    {
        if ($this->writeFails) {
            return ['ok' => false, 'error' => 'write failed'];
        }

        $templates = 0;
        foreach ($users as $user) {
            $this->users[$device->id][$user['user_id']] = $user;
            $templates += count($user['fingers'] ?? []);
        }

        return ['ok' => true, 'written' => count($users), 'templates' => $templates];
    }

    public function delete(AttendanceMachine $device, array $deviceUserIds): array
    {
        $deleted = 0;
        foreach ($deviceUserIds as $id) {
            if (isset($this->users[$device->id][$id])) {
                unset($this->users[$device->id][$id]);
                $deleted++;
            }
        }

        return ['ok' => true, 'deleted' => $deleted];
    }
}
