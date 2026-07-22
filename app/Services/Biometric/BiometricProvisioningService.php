<?php

namespace App\Services\Biometric;

use App\Models\AttendanceMachine;
use App\Models\DeviceEnrollment;
use App\Models\Employee;
use Illuminate\Support\Collection;

/**
 * Provisions employee identities + fingerprint templates across physical devices.
 *
 * "Copy" reads users + templates from a source device and writes them to targets.
 * "Move" copies then removes the users from the source (only when every target
 * write succeeded, so a failed push never loses the source enrolment).
 * "Delete" removes the users from a device. The device_enrollments table is kept
 * in step with whatever actually happened on the hardware.
 */
class BiometricProvisioningService
{
    public function __construct(private readonly ZktecoDeviceGateway $gateway)
    {
    }

    /**
     * @param  Collection<int, AttendanceMachine>  $targets
     * @param  Collection<int, Employee>  $employees
     * @return array<string, mixed>
     */
    public function copy(AttendanceMachine $source, Collection $targets, Collection $employees): array
    {
        return $this->push($source, $targets, $employees, deleteSource: false);
    }

    /**
     * @param  Collection<int, AttendanceMachine>  $targets
     * @param  Collection<int, Employee>  $employees
     * @return array<string, mixed>
     */
    public function move(AttendanceMachine $source, Collection $targets, Collection $employees): array
    {
        return $this->push($source, $targets, $employees, deleteSource: true);
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return array<string, mixed>
     */
    public function delete(AttendanceMachine $device, Collection $employees): array
    {
        $userIds = $this->userIds($employees);

        if ($userIds->isEmpty()) {
            return ['ok' => false, 'error' => __('app.provision.no_employees')];
        }

        $result = $this->gateway->delete($device, $userIds->all());

        if (! ($result['ok'] ?? false)) {
            return ['ok' => false, 'error' => $result['error'] ?? __('app.provision.device_failed')];
        }

        $this->forgetEnrollments($device, $employees);

        return ['ok' => true, 'deleted' => $result['deleted'] ?? 0];
    }

    /**
     * @param  Collection<int, AttendanceMachine>  $targets
     * @param  Collection<int, Employee>  $employees
     * @return array<string, mixed>
     */
    private function push(AttendanceMachine $source, Collection $targets, Collection $employees, bool $deleteSource): array
    {
        $userIds = $this->userIds($employees);

        if ($userIds->isEmpty()) {
            return ['ok' => false, 'error' => __('app.provision.no_employees')];
        }

        if ($targets->isEmpty()) {
            return ['ok' => false, 'error' => __('app.enroll.no_target')];
        }

        $read = $this->gateway->read($source, $userIds->all());

        if (! ($read['ok'] ?? false)) {
            return ['ok' => false, 'error' => $read['error'] ?? __('app.provision.device_failed')];
        }

        $users = $read['users'] ?? [];

        if (empty($users)) {
            return ['ok' => false, 'error' => __('app.provision.none_on_source')];
        }

        $foundIds = collect($users)->pluck('user_id')->map(fn ($id) => (string) $id)->unique();

        $written = 0;
        $templates = 0;
        $failedTargets = [];

        foreach ($targets as $target) {
            $result = $this->gateway->write($target, $users);

            if (! ($result['ok'] ?? false)) {
                $failedTargets[] = $target->device_name;

                continue;
            }

            $written += $result['written'] ?? 0;
            $templates += $result['templates'] ?? 0;
            $this->recordEnrollments($target, $employees, $foundIds, $source->id);
        }

        $stats = [
            'ok' => true,
            'users' => count($users),
            'templates' => $templates,
            'written' => $written,
            'targets' => $targets->count() - count($failedTargets),
            'failed_targets' => $failedTargets,
            'missing' => $userIds->reject(fn ($id) => $foundIds->contains($id))->count(),
            'source_deleted' => 0,
        ];

        // Only clear the source once every target push succeeded.
        if ($deleteSource && empty($failedTargets)) {
            $sourceEmployees = $employees->filter(fn (Employee $e) => $foundIds->contains((string) $e->hr_employee_id));
            $deleteResult = $this->gateway->delete($source, $foundIds->all());

            if ($deleteResult['ok'] ?? false) {
                $this->forgetEnrollments($source, $sourceEmployees);
                $stats['source_deleted'] = $deleteResult['deleted'] ?? 0;
            } else {
                $stats['source_error'] = $deleteResult['error'] ?? __('app.provision.device_failed');
            }
        }

        return $stats;
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, string>
     */
    private function userIds(Collection $employees): Collection
    {
        return $employees->pluck('hr_employee_id')
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @param  Collection<int, string>  $foundIds
     */
    private function recordEnrollments(AttendanceMachine $target, Collection $employees, Collection $foundIds, int $sourceId): void
    {
        foreach ($employees as $employee) {
            if (! $foundIds->contains((string) $employee->hr_employee_id)) {
                continue;
            }

            DeviceEnrollment::updateOrCreate(
                ['attendance_machine_id' => $target->id, 'employee_id' => $employee->id],
                ['device_user_id' => $employee->hr_employee_id, 'source_machine_id' => $sourceId, 'enrolled_at' => now()],
            );
        }
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    private function forgetEnrollments(AttendanceMachine $device, Collection $employees): void
    {
        DeviceEnrollment::where('attendance_machine_id', $device->id)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->delete();
    }
}
