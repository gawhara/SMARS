<?php

namespace Tests\Feature;

use App\Models\AttendanceMachine;
use App\Models\Company;
use App\Models\DeviceEnrollment;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceEnrollmentTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_can_enroll_employees_on_a_device(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $device = $this->device('Gate A');
        $e1 = $this->employee('EMP-1');
        $e2 = $this->employee('EMP-2');

        $this->actingAs($user)->post(route('devices.enrollments.store', $device), [
            'employee_ids' => [$e1->id, $e2->id],
        ])->assertRedirect();

        $this->assertSame(2, $device->enrollments()->count());
        $this->assertDatabaseHas('device_enrollments', [
            'attendance_machine_id' => $device->id, 'employee_id' => $e1->id, 'device_user_id' => 'EMP-1',
        ]);
    }

    public function test_copy_enrollments_to_all_devices_skips_existing(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $source = $this->device('Source');
        $target1 = $this->device('Target 1');
        $target2 = $this->device('Target 2');
        $e1 = $this->employee('EMP-1');
        $e2 = $this->employee('EMP-2');

        // Enrol both on source; pre-enrol EMP-1 on target1 so it is skipped.
        foreach ([$e1, $e2] as $e) {
            DeviceEnrollment::create(['attendance_machine_id' => $source->id, 'employee_id' => $e->id, 'device_user_id' => $e->employee_code, 'enrolled_at' => now()]);
        }
        DeviceEnrollment::create(['attendance_machine_id' => $target1->id, 'employee_id' => $e1->id, 'device_user_id' => $e1->employee_code, 'enrolled_at' => now()]);

        $this->actingAs($user)->post(route('devices.enrollments.copy', $source), ['target' => 'all'])
            ->assertRedirect();

        // target1 gains EMP-2 only (EMP-1 already there); target2 gains both.
        $this->assertSame(2, $target1->enrollments()->count());
        $this->assertSame(2, $target2->enrollments()->count());
        $this->assertDatabaseHas('device_enrollments', [
            'attendance_machine_id' => $target2->id, 'employee_id' => $e1->id, 'source_machine_id' => $source->id,
        ]);
    }

    public function test_copy_to_single_target_device(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $source = $this->device('Source');
        $target = $this->device('Target');
        $other = $this->device('Untouched');
        $e1 = $this->employee('EMP-1');
        DeviceEnrollment::create(['attendance_machine_id' => $source->id, 'employee_id' => $e1->id, 'device_user_id' => 'EMP-1', 'enrolled_at' => now()]);

        $this->actingAs($user)->post(route('devices.enrollments.copy', $source), ['target' => $target->id])
            ->assertRedirect();

        $this->assertSame(1, $target->enrollments()->count());
        $this->assertSame(0, $other->enrollments()->count());
    }
}
