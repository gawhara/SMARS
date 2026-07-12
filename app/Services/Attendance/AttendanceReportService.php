<?php

namespace App\Services\Attendance;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates attendance over a date range into per-employee summary rows
 * (present / late / absent / rest days, punches, and worked hours).
 */
class AttendanceReportService
{
    public function __construct(private readonly AttendanceMatrixService $matrix)
    {
    }

    /**
     * @param  Collection<int, \App\Models\Employee>  $employees
     * @param  Collection<int, \App\Models\AttendanceRecord>  $records
     * @return array<int, array<string, mixed>>
     */
    public function build(Carbon $from, Carbon $to, Collection $employees, Collection $records, ?Collection $holidays = null, ?Collection $leaves = null): array
    {
        $today = Carbon::today();
        $holidays ??= collect();
        $leaves ??= collect();
        $byEmployee = $records->groupBy('employee_id')
            ->map(fn ($group) => $group->groupBy(fn ($r) => $r->punch_at->toDateString()));

        $rows = [];

        foreach ($employees as $employee) {
            $shiftStart = $this->matrix->shiftStart($employee);
            $policy = app(AttendancePolicyService::class)->forEmployee($employee);
            $counts = ['present' => 0, 'late' => 0, 'absent' => 0, 'rest' => 0, 'holiday' => 0, 'leave' => 0];
            $punchTotal = 0;
            $minutes = 0;

            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                $dayPunches = $byEmployee[$employee->id][$date->toDateString()] ?? collect();
                $calendarStatus = $this->matrix->calendarStatus($employee, $date, $holidays, $leaves);
                $status = $this->matrix->dayStatus($date, $today, $dayPunches, $shiftStart, $policy->grace_minutes, $policy->weekend_days ?? [5], $calendarStatus);

                if (isset($counts[$status])) {
                    $counts[$status]++;
                }

                if ($dayPunches->isNotEmpty()) {
                    $punchTotal += $dayPunches->count();
                    $minutes += $this->pairedMinutes($dayPunches);
                }
            }

            $rows[] = [
                'employee' => $employee,
                'present' => $counts['present'],
                'late' => $counts['late'],
                'absent' => $counts['absent'],
                'rest' => $counts['rest'],
                'holiday' => $counts['holiday'],
                'leave' => $counts['leave'],
                'worked_days' => $counts['present'] + $counts['late'],
                'punches' => $punchTotal,
                'hours' => round($minutes / 60, 1),
            ];
        }

        return $rows;
    }

    private function pairedMinutes(Collection $punches): int
    {
        $open = null;
        $minutes = 0;
        foreach ($punches->sortBy('punch_at') as $punch) {
            if ($punch->punch_type === 'in' && $open === null) $open = $punch;
            if ($punch->punch_type === 'out' && $open !== null) {
                $minutes += (int) $open->punch_at->diffInMinutes($punch->punch_at);
                $open = null;
            }
        }
        return $minutes;
    }
}
