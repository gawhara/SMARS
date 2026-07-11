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
    public function build(Carbon $from, Carbon $to, Collection $employees, Collection $records): array
    {
        $today = Carbon::today();
        $byEmployee = $records->groupBy('employee_id')
            ->map(fn ($group) => $group->groupBy(fn ($r) => $r->punch_at->toDateString()));

        $rows = [];

        foreach ($employees as $employee) {
            $shiftStart = $this->matrix->shiftStart($employee);
            $counts = ['present' => 0, 'late' => 0, 'absent' => 0, 'rest' => 0];
            $punchTotal = 0;
            $minutes = 0;

            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                $dayPunches = $byEmployee[$employee->id][$date->toDateString()] ?? collect();
                $status = $this->matrix->dayStatus($date, $today, $dayPunches, $shiftStart);

                if (isset($counts[$status])) {
                    $counts[$status]++;
                }

                if ($dayPunches->isNotEmpty()) {
                    $punchTotal += $dayPunches->count();
                    $first = $dayPunches->min(fn ($r) => $r->punch_at->format('H:i:s'));
                    $last = $dayPunches->max(fn ($r) => $r->punch_at->format('H:i:s'));
                    $minutes += Carbon::createFromFormat('H:i:s', $first)
                        ->diffInMinutes(Carbon::createFromFormat('H:i:s', $last));
                }
            }

            $rows[] = [
                'employee' => $employee,
                'present' => $counts['present'],
                'late' => $counts['late'],
                'absent' => $counts['absent'],
                'rest' => $counts['rest'],
                'worked_days' => $counts['present'] + $counts['late'],
                'punches' => $punchTotal,
                'hours' => round($minutes / 60, 1),
            ];
        }

        return $rows;
    }
}
