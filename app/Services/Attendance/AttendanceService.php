<?php

namespace App\Services\Attendance;

use App\Models\AttendanceMachine;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSyncBatch;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Resolve a device user id to a global employee (CODEX §19). Matching is
     * against employee_code first, then the HR employee id as a fallback.
     */
    public function resolveEmployee(?string $deviceUserId): ?Employee
    {
        $deviceUserId = trim((string) $deviceUserId);

        if ($deviceUserId === '') {
            return null;
        }

        return Employee::query()
            ->where('hr_employee_id', $deviceUserId)
            ->orWhere('employee_code', $deviceUserId)
            ->first();
    }

    public function normalizePunchType(?string $raw): string
    {
        return match (strtolower(trim((string) $raw))) {
            'in', 'checkin', 'check-in', 'check_in', 'i', '0' => 'in',
            'out', 'checkout', 'check-out', 'check_out', 'o', '1' => 'out',
            default => 'unknown',
        };
    }

    /**
     * Import a CSV of punches, matching each to an employee and skipping duplicates.
     * Expected header: device_user_id, punch_at, punch_type, verification_type
     */
    public function importCsv(UploadedFile $file, ?AttendanceMachine $machine, int $userId): AttendanceSyncBatch
    {
        $rows = $this->readCsv($file->getRealPath());

        return DB::transaction(function () use ($rows, $file, $machine, $userId): AttendanceSyncBatch {
            $batch = AttendanceSyncBatch::create([
                'source' => 'import',
                'file_name' => $file->getClientOriginalName(),
                'attendance_machine_id' => $machine?->id,
                'total_rows' => count($rows),
                'created_by' => $userId,
            ]);

            $imported = $matched = $unmatched = $duplicate = 0;

            foreach ($rows as $row) {
                $deviceUserId = $row['device_user_id'] ?? null;
                $punchRaw = $row['punch_at'] ?? null;

                if (! $deviceUserId || ! $punchRaw) {
                    continue;
                }

                try {
                    $punchAt = Carbon::parse($punchRaw);
                } catch (\Throwable) {
                    continue;
                }

                $exists = AttendanceRecord::where('attendance_machine_id', $machine?->id)
                    ->where('device_user_id', $deviceUserId)
                    ->where('punch_at', $punchAt)
                    ->exists();

                if ($exists) {
                    $duplicate++;

                    continue;
                }

                $employee = $this->resolveEmployee($deviceUserId);

                AttendanceRecord::create([
                    'employee_id' => $employee?->id,
                    'attendance_machine_id' => $machine?->id,
                    'device_user_id' => $deviceUserId,
                    'punch_at' => $punchAt,
                    'punch_type' => $this->normalizePunchType($row['punch_type'] ?? null),
                    'raw_punch_type' => $row['punch_type'] ?? null,
                    'verification_type' => $row['verification_type'] ?? null,
                    'source' => 'import',
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
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return $rows;
        }

        $header = null;

        while (($data = fgetcsv($handle)) !== false) {
            // Skip fully empty lines.
            if ($data === [null] || (count($data) === 1 && trim((string) $data[0]) === '')) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $data);

                continue;
            }

            $row = [];
            foreach ($header as $index => $key) {
                $row[$key] = isset($data[$index]) ? trim((string) $data[$index]) : null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
