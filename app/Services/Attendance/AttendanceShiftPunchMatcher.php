<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceShiftPunchMatcher
{
    /**
     * Match each scheduled period start to an IN punch and each period end to an OUT punch.
     * A punch is used once, choosing the closest same-day punch to the configured shift time.
     */
    public function match(Employee $employee, Carbon|string $date, Collection $punches): array
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $periods = $employee->shift?->schedule_id
            ? Shift::where('schedule_id', $employee->shift->schedule_id)->orderBy('shift_number')->get()
            : collect([$employee->shift])->filter();

        $unused = $punches->sortBy('punch_at')->values();

        return $periods->map(function (Shift $period) use ($date, &$unused): array {
            $scheduledIn = $this->atDate($date, $period->start_time);
            $scheduledOut = $this->atDate($date, $period->end_time);
            if ($scheduledOut->lte($scheduledIn)) {
                $scheduledOut->addDay();
            }

            $actualIn = $this->takeClosest($unused, 'in', $scheduledIn);
            $actualOut = $this->takeClosest($unused, 'out', $scheduledOut);

            return [
                'number' => $period->shift_number,
                'scheduled_in' => $scheduledIn,
                'actual_in' => $actualIn?->punch_at,
                'scheduled_out' => $scheduledOut,
                'actual_out' => $actualOut?->punch_at,
            ];
        })->values()->all();
    }

    private function takeClosest(Collection &$punches, string $type, Carbon $target): mixed
    {
        $candidate = $punches
            ->filter(fn ($punch) => $punch->punch_type === $type)
            ->sortBy(fn ($punch) => abs($punch->punch_at->diffInSeconds($target, false)))
            ->first();

        if ($candidate) {
            $punches = $punches->reject(fn ($punch) => $punch->id === $candidate->id)->values();
        }

        return $candidate;
    }

    private function atDate(Carbon $date, mixed $time): Carbon
    {
        return Carbon::parse($date->toDateString().' '.substr((string) $time, 0, 8));
    }
}
