<?php

namespace App\Services\Attendance;

use App\Models\AttendanceDailySummary;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\AttendanceHoliday;
use App\Models\EmployeeLeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceDailySummaryService
{
    public function __construct(private readonly AttendancePolicyService $policies)
    {
    }

    public function rebuild(Employee $employee, Carbon|string $date): ?AttendanceDailySummary
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $employee->loadMissing('shift');
        $policy = $this->policies->forEmployee($employee);

        $punches = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('punch_at', $date)
            ->orderBy('punch_at')
            ->get();

        if ($punches->isEmpty()) {
            $calendarStatus = $this->calendarStatus($employee, $date);
            if ($calendarStatus !== null) {
                $scheduleShifts = $this->scheduleShifts($employee);
                $summary = AttendanceDailySummary::where('employee_id', $employee->id)->whereDate('attendance_date', $date)->first()
                    ?? new AttendanceDailySummary(['employee_id' => $employee->id, 'attendance_date' => $date->toDateString()]);
                $summary->fill([
                    'first_in_at' => null, 'last_out_at' => null, 'punch_count' => 0,
                    'worked_minutes' => 0,
                    'scheduled_minutes' => $scheduleShifts->isNotEmpty() ? $scheduleShifts->sum->durationMinutes() : (int) $policy->full_day_minutes,
                    'late_minutes' => 0, 'early_leave_minutes' => 0, 'overtime_minutes' => 0,
                    'status' => $calendarStatus, 'has_exception' => false, 'exception_codes' => [], 'calculated_at' => now(),
                ])->save();
                return $summary;
            }

            AttendanceDailySummary::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->delete();

            return null;
        }

        $exceptions = [];
        $openPunch = null;
        $workedMinutes = 0;
        $firstIn = null;
        $lastOut = null;
        $lastPunchType = null;
        $completedSessions = 0;

        foreach ($punches as $punch) {
            if ($punch->punch_type === 'in') {
                $firstIn ??= $punch->punch_at;

                if ($openPunch !== null) {
                    $exceptions[] = 'repeated_in';
                    $lastPunchType = 'in';
                    continue;
                }

                $openPunch = $punch;
                $lastPunchType = 'in';
                continue;
            }

            if ($punch->punch_type === 'out') {
                $lastOut = $punch->punch_at;

                if ($openPunch === null) {
                    $exceptions[] = $lastPunchType === 'out' ? 'repeated_out' : 'missing_in';
                    $lastPunchType = 'out';
                    continue;
                }

                $workedMinutes += max(0, (int) $openPunch->punch_at->diffInMinutes($punch->punch_at));
                $completedSessions++;
                $openPunch = null;
                $lastPunchType = 'out';
                continue;
            }

            $exceptions[] = 'unknown_type';
        }

        if ($openPunch !== null) {
            $exceptions[] = 'missing_out';
        }

        $scheduleShifts = $this->scheduleShifts($employee);
        if ($scheduleShifts->count() > 1 && $completedSessions < $scheduleShifts->count()) {
            $exceptions[] = 'missing_period';
        }

        $exceptions = array_values(array_unique($exceptions));
        $rounding = max(1, (int) $policy->rounding_minutes);
        $workedMinutes = (int) (round($workedMinutes / $rounding) * $rounding);
        $firstScheduledShift = $scheduleShifts->first();
        $lastScheduledShift = $scheduleShifts->last();
        $shiftStart = $this->shiftDateTime($firstScheduledShift?->start_time, $date);
        $shiftEnd = $this->shiftDateTime($lastScheduledShift?->end_time, $date);
        $lateMinutes = $firstIn && $shiftStart
            ? max(0, (int) $shiftStart->copy()->addMinutes($policy->grace_minutes)->diffInMinutes($firstIn, false))
            : 0;
        $earlyLeaveMinutes = $lastOut && $shiftEnd
            ? max(0, (int) $lastOut->copy()->addMinutes($policy->early_leave_grace_minutes)->diffInMinutes($shiftEnd, false))
            : 0;
        $scheduledMinutes = $scheduleShifts->isNotEmpty() ? $scheduleShifts->sum->durationMinutes() : (int) $policy->full_day_minutes;
        $status = $exceptions !== []
            ? 'incomplete'
            : ($workedMinutes < $policy->full_day_minutes && $workedMinutes >= $policy->half_day_minutes
                ? 'half_day'
                : ($lateMinutes > 0 ? 'late' : 'present'));

        $summary = AttendanceDailySummary::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->first() ?? new AttendanceDailySummary([
                'employee_id' => $employee->id,
                'attendance_date' => $date->toDateString(),
            ]);

        $summary->fill([
                'first_in_at' => $firstIn,
                'last_out_at' => $lastOut,
                'punch_count' => $punches->count(),
                'worked_minutes' => $workedMinutes,
                'scheduled_minutes' => $scheduledMinutes,
                'late_minutes' => $lateMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'overtime_minutes' => max(0, $workedMinutes - (int) $policy->overtime_after_minutes),
                'status' => $status,
                'has_exception' => $exceptions !== [],
                'exception_codes' => $exceptions,
                'calculated_at' => now(),
            ])->save();

        return $summary;
    }

    public function rebuildForRecords(Collection $records): void
    {
        $records->filter(fn ($record) => $record->employee_id !== null)
            ->groupBy(fn ($record) => $record->employee_id.'|'.$record->punch_at->toDateString())
            ->each(function (Collection $group): void {
                $record = $group->first();
                $employee = Employee::find($record->employee_id);
                if ($employee) {
                    $this->rebuild($employee, $record->punch_at);
                }
            });
    }

    public function rebuildRange(Employee $employee, Carbon|string $from, Carbon|string $to): void
    {
        $date = $from instanceof Carbon ? $from->copy() : Carbon::parse($from);
        $end = $to instanceof Carbon ? $to->copy() : Carbon::parse($to);
        while ($date->lte($end)) {
            $this->rebuild($employee, $date);
            $date->addDay();
        }
    }

    private function scheduleShifts(Employee $employee): Collection
    {
        return $employee->shift?->schedule_id
            ? Shift::where('schedule_id', $employee->shift->schedule_id)->orderBy('shift_number')->get()
            : collect([$employee->shift])->filter();
    }

    private function calendarStatus(Employee $employee, Carbon $date): ?string
    {
        $onLeave = EmployeeLeaveRequest::where('employee_id', $employee->id)->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->exists();
        if ($onLeave) return 'leave';

        $holiday = AttendanceHoliday::where('is_active', true)->whereDate('holiday_date', $date)
            ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $employee->company_id))->exists();
        return $holiday ? 'holiday' : null;
    }

    private function shiftDateTime(mixed $value, Carbon $date): ?Carbon
    {
        return $value ? Carbon::parse($date->toDateString().' '.substr((string) $value, 0, 8)) : null;
    }
}
