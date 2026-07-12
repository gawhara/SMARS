<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\Shift;
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
    private const DEFAULT_START = '08:00';

    public function __construct(private readonly AttendancePolicyService $policies)
    {
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @param  Collection<int, \App\Models\AttendanceRecord>  $records
     * @return array<int, array{days: array<int, string>, summary: array<string, int>}>
     */
    public function build(Carbon $month, Collection $employees, Collection $records, ?Collection $holidays = null, ?Collection $leaves = null): array
    {
        $start = $month->copy()->startOfMonth();
        $daysInMonth = $start->daysInMonth;
        $today = Carbon::today();
        $holidays ??= collect();
        $leaves ??= collect();

        // employee_id => [ 'Y-m-d' => Collection<records> ]
        $byEmployee = $records->groupBy('employee_id')
            ->map(fn ($group) => $group->groupBy(fn ($r) => $r->punch_at->toDateString()));

        $matrix = [];

        foreach ($employees as $employee) {
            $shiftStart = $this->shiftStart($employee);
            $policy = $this->policies->forEmployee($employee);

            $days = [];
            $summary = ['present' => 0, 'late' => 0, 'absent' => 0, 'rest' => 0, 'holiday' => 0, 'leave' => 0];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $start->copy()->day($day);
                $status = $this->dayStatus(
                    $date,
                    $today,
                    $byEmployee[$employee->id][$date->toDateString()] ?? collect(),
                    $shiftStart,
                    $policy->grace_minutes,
                    $policy->weekend_days ?? [5],
                    $this->calendarStatus($employee, $date, $holidays, $leaves),
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
        $shift = $employee->shift?->schedule_id
            ? Shift::where('schedule_id', $employee->shift->schedule_id)->orderBy('shift_number')->first()
            : $employee->shift;

        return $shift?->start_time
            ? substr((string) $shift->start_time, 0, 5)
            : self::DEFAULT_START;
    }

    /**
     * Attendance status for a single day, reused by the report service.
     */
    public function dayStatus(Carbon $date, Carbon $today, Collection $punches, string $shiftStart, int $graceMinutes = 10, array $weekendDays = [5], ?string $calendarStatus = null): string
    {
        // A punch means the employee attended, even on a weekend.
        if ($punches->isNotEmpty()) {
            $firstPunch = $punches->min(fn ($r) => $r->punch_at->format('H:i'));
            $threshold = Carbon::createFromFormat('H:i', $shiftStart)->addMinutes($graceMinutes)->format('H:i');

            return $firstPunch > $threshold ? 'late' : 'present';
        }

        if ($calendarStatus !== null) {
            return $calendarStatus;
        }

        // No punches: Friday is the weekly rest day in Saudi Arabia.
        if (in_array($date->dayOfWeek, $weekendDays, true)) {
            return 'rest';
        }

        // A workday in the past with no punch is an absence; future days are blank.
        return $date->gt($today) ? 'future' : 'absent';
    }

    public function calendarStatus(Employee $employee, Carbon $date, Collection $holidays, Collection $leaves): ?string
    {
        $onLeave = $leaves->contains(fn ($leave) => $leave->employee_id === $employee->id && $date->betweenIncluded($leave->start_date, $leave->end_date));
        if ($onLeave) return 'leave';
        $holiday = $holidays->contains(fn ($item) => $item->holiday_date->isSameDay($date) && ($item->company_id === null || $item->company_id === $employee->company_id));
        return $holiday ? 'holiday' : null;
    }
}
