<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Per-day deduction evaluation (policy sections 2, 12, 16–25).
 *
 * Matches punches to each required shift (one/two/rotating), computes per-shift
 * late/early minutes and missing punches, and turns them into deduction hours
 * via AttendanceDeductionService — applying the duplicate-penalty prevention
 * rules so the same event is never charged twice:
 *   - Missing check-out → 3h missing, and NO early departure for that shift (§17).
 *   - Missing check-in  → 3h missing, and NO late entry for that shift (§16).
 *   - Both punches missing → unresolved; no auto missing/absence charge (§18).
 *   - A shift charged as missing/absent is never also charged the other (§25).
 *
 * Rest / holiday / leave days incur no deductions (§36).
 */
class AttendanceDeductionEvaluator
{
    public function __construct(
        private readonly AttendanceShiftPunchMatcher $matcher,
        private readonly AttendanceDeductionService $deductions,
    ) {
    }

    /**
     * @param  Collection<int, \App\Models\AttendanceRecord>  $punches
     * @return array<string, mixed>
     */
    public function evaluate(Employee $employee, Carbon|string $date, Collection $punches, ?string $calendarStatus = null): array
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        if (in_array($calendarStatus, ['rest', 'holiday', 'leave'], true)) {
            return $this->nonWorkingDay($date, $calendarStatus);
        }

        $periods = $this->matcher->match($employee, $date, $punches);
        $requiredShifts = count($periods);
        $attendedShifts = 0;
        $deductionHours = 0;
        $needsReview = false;
        $shifts = [];

        foreach ($periods as $p) {
            $hasIn = $p['actual_in'] !== null;
            $hasOut = $p['actual_out'] !== null;

            $lateMin = ($hasIn && $p['scheduled_in']->lt($p['actual_in']))
                ? (int) $p['scheduled_in']->diffInMinutes($p['actual_in'])
                : 0;
            $earlyMin = ($hasOut && $p['actual_out']->lt($p['scheduled_out']))
                ? (int) $p['actual_out']->diffInMinutes($p['scheduled_out'])
                : 0;

            $late = $early = $missing = 0;

            if ($hasIn && $hasOut) {
                $late = $this->deductions->lateDeductionHours($lateMin);
                $early = $this->deductions->earlyDeductionHours($earlyMin);
                $status = $late > 0 ? 'late' : 'present';
                $attendedShifts++;
            } elseif ($hasIn) {
                // Missing check-out (§17): late still counts, early does not, +3h.
                $late = $this->deductions->lateDeductionHours($lateMin);
                $missing = $this->deductions->missingPunchHours(1);
                $status = 'missing_out';
                $attendedShifts++;
                $needsReview = true;
            } elseif ($hasOut) {
                // Missing check-in (§16): early still counts, late does not, +3h.
                $missing = $this->deductions->missingPunchHours(1);
                $early = $this->deductions->earlyDeductionHours($earlyMin);
                $status = 'missing_in';
                $attendedShifts++;
                $needsReview = true;
            } else {
                // Both missing (§18): unresolved — reviewed before any charge.
                $status = 'unresolved';
                $needsReview = true;
            }

            $shiftHours = $late + $early + $missing;
            $deductionHours += $shiftHours;

            $shifts[] = [
                'number' => $p['number'],
                'scheduled_in' => $p['scheduled_in']->format('H:i'),
                'scheduled_out' => $p['scheduled_out']->format('H:i'),
                'actual_in' => $p['actual_in']?->format('H:i'),
                'actual_out' => $p['actual_out']?->format('H:i'),
                'late_minutes' => $lateMin,
                'early_minutes' => $earlyMin,
                'late_deduction_hours' => $late,
                'early_deduction_hours' => $early,
                'missing_punch_hours' => $missing,
                'deduction_hours' => $shiftHours,
                'status' => $status,
            ];
        }

        // Absence for shifts with no attendance at all (§19–21). Never combined
        // with a missing-punch charge for the same shift (those count as attended).
        $absence = $this->deductions->absence($requiredShifts, $attendedShifts);
        if ($absence['penalty_days'] > 0) {
            $needsReview = true;
        }

        return [
            'date' => $date->toDateString(),
            'calendar_status' => $calendarStatus,
            'required_shifts' => $requiredShifts,
            'attended_shifts' => $attendedShifts,
            'shifts' => $shifts,
            'deduction_hours' => $deductionHours,
            'absence' => $absence,
            'needs_review' => $needsReview,
            'status' => $this->dayStatus($requiredShifts, $attendedShifts, $shifts),
        ];
    }

    private function dayStatus(int $required, int $attended, array $shifts): string
    {
        if ($required === 0) {
            return 'no_shift';
        }
        if ($attended === 0) {
            return 'absent';
        }
        if ($attended < $required) {
            return 'partial';
        }

        $statuses = array_column($shifts, 'status');
        if (array_intersect($statuses, ['missing_in', 'missing_out', 'unresolved'])) {
            return 'incomplete';
        }

        return in_array('late', $statuses, true) ? 'late' : 'present';
    }

    /**
     * @return array<string, mixed>
     */
    private function nonWorkingDay(Carbon $date, ?string $calendarStatus): array
    {
        return [
            'date' => $date->toDateString(),
            'calendar_status' => $calendarStatus,
            'required_shifts' => 0,
            'attended_shifts' => 0,
            'shifts' => [],
            'deduction_hours' => 0,
            'absence' => ['absence_fraction' => 0.0, 'missed_shifts' => 0, 'penalty_days' => 0],
            'needs_review' => false,
            'status' => $calendarStatus ?? 'rest',
        ];
    }
}
