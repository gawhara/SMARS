<?php

namespace App\Services\Biometric;

use App\Models\AttendanceMachine;
use Symfony\Component\Process\Process;

/**
 * Thin wrapper around scripts/zkteco_provision.py. This is the single seam where
 * the application talks to physical devices for WRITE operations, so it can be
 * swapped for a fake in tests (no hardware required).
 */
class ZktecoDeviceGateway
{
    /**
     * Read the given device-user records + fingerprint templates from a device.
     *
     * @param  array<int, string>  $deviceUserIds
     * @return array{ok: bool, users?: array, error?: string}
     */
    public function read(AttendanceMachine $device, array $deviceUserIds): array
    {
        return $this->run($device, 'read', ['--ids', implode(',', $deviceUserIds)]);
    }

    /**
     * Create/update the given users and save their templates on a device.
     *
     * @param  array<int, array<string, mixed>>  $users
     * @return array{ok: bool, written?: int, templates?: int, failed?: array, error?: string}
     */
    public function write(AttendanceMachine $device, array $users): array
    {
        $payload = tempnam(sys_get_temp_dir(), 'zkprov_');
        file_put_contents($payload, json_encode(['users' => $users], JSON_UNESCAPED_UNICODE));

        try {
            return $this->run($device, 'write', ['--input', $payload]);
        } finally {
            @unlink($payload);
        }
    }

    /**
     * Delete the given users (and their templates) from a device.
     *
     * @param  array<int, string>  $deviceUserIds
     * @return array{ok: bool, deleted?: int, failed?: array, error?: string}
     */
    public function delete(AttendanceMachine $device, array $deviceUserIds): array
    {
        return $this->run($device, 'delete', ['--ids', implode(',', $deviceUserIds)]);
    }

    /**
     * @param  array<int, string>  $extra
     * @return array<string, mixed>
     */
    protected function run(AttendanceMachine $device, string $action, array $extra): array
    {
        if (! $device->host()) {
            return ['ok' => false, 'error' => __('app.device.no_target')];
        }

        $process = new Process(array_merge([
            config('services.zkteco.python'), base_path('scripts/zkteco_provision.py'),
            '--ip', (string) $device->host(),
            '--port', (string) $device->port,
            '--comm-key', (string) $device->comm_key,
            '--timeout', '15',
            '--action', $action,
        ], $extra));
        $process->setTimeout(90);
        $process->run();

        $data = json_decode($process->getOutput(), true);

        if (! $process->isSuccessful() || ! is_array($data) || ! ($data['ok'] ?? false)) {
            $error = (is_array($data) ? ($data['error'] ?? null) : null)
                ?: ($process->getErrorOutput() ?: __('app.provision.device_failed'));

            return ['ok' => false, 'error' => $error];
        }

        return $data;
    }
}
