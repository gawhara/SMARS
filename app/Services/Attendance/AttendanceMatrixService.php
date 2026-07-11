<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Derives a per-employee, per-day attendance status grid for a month.
 *
 * Statuses: present | late | absent | rest | future
 * (holiday / leave are reserved for the documents & leave modules.)
 */
class AttendanceMatrixService
{
    private const GRACE_MINUTES = 15;
    private const DEFAULT_START = '08:00';

    /**
     * @param  Collection<int, Employee>  $employees
     * @param  Collection<int, \App\Models\AttendanceRecord>  $records
     * @return array<int, array{days: array<int, string>, summary: array<string, int>}>
     */
    public function build(Carbon $month, Collection $employees, Collection $records): array
    {
        $start = $month->copy()->startOfMonth();
        $daysInMonth = $start->daysInMonth;
        $today = Carbon::today();

        // employee_id => [ 'Y-m-d' => Collection<records> ]
        $byEmployee = $records->groupBy('employee_id')
            ->map(fn ($group) => $group->groupBy(fn ($r) => $r->punch_at->toDateString()));

        $matrix = [];

        foreach ($employees as $employee) {
            $shiftStart = $this->shiftStart($employee);

            $days = [];
            $summary = ['present' => 0, 'late' => 0, 'absent' => 0, 'rest' => 0];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $start->copy()->day($day);
                $status = $this->dayStatus(
                    $date,
                    $today,
                    $byEmployee[$employee->id][$date->toDateString()] ?? collect(),
                    $shiftStart,
                );

                $days[$day] = $status;
                if (isset($summary[$status])) {
                    $summary[$status]++;
                }
            }

            $matrix[$employee->id] = ['days' => $days, 'summary' => $summary];
        }

        return $matrix;
    }

    public function shiftStart(Employee $employee): string
    {
        return $employee->shift?->start_time
            ? substr((string) $employee->shift->start_time, 0, 5)
            : self::DEFAULT_START;
    }

    /**
     * Attendance status for a single day, reused by the report service.
     */
    public function dayStatus(Carbon $date, Carbon $today, Collection $punches, string $shiftStart): string
    {
        // A punch means the employee attended, even on a weekend.
        if ($punches->isNotEmpty()) {
            $firstPunch = $punches->min(fn ($r) => $r->punch_at->format('H:i'));
            $threshold = Carbon::createFromFormat('H:i', $shiftStart)->addMinutes(self::GRACE_MINUTES)->format('H:i');

            return $firstPunch > $threshold ? 'late' : 'present';
        }

        // No punches: KSA weekend is a rest day.
        if ($date->isFriday() || $date->isSaturday()) {
            return 'rest';
        }

        // A workday in the past with no punch is an absence; future days are blank.
        return $date->gt($today) ? 'future' : 'absent';
    }
}
