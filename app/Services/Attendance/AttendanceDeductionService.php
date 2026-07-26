<?php

namespace App\Services\Attendance;

/**
 * Attendance-deduction calculation engine (policy sections 3–25).
 *
 * Pure, side-effect-free math over minutes and shift counts. Higher layers feed
 * it actual late/early minutes and shift attendance, and convert its "deduction
 * hours" into money using the hourly rate (sections 28–33).
 *
 * Core rules:
 *   - Late entry: 9-minute grace (once), then round total lateness UP to whole
 *     hours and multiply by 2.
 *   - Early departure: same rounding + ×2, but no grace (counts from minute 1).
 *   - Missing punch: a flat 3 chargeable hours per unapproved missing punch.
 *   - Absence: one-shift vs two-shift penalty days (sections 19–21).
 */
class AttendanceDeductionService
{
    public const LATE_GRACE_MINUTES = 9;

    public const PENALTY_MULTIPLIER = 2;

    public const MISSING_PUNCH_HOURS = 3;

    // ---- Late entry (sections 3–6) ----

    /**
     * Chargeable lateness hours before the penalty multiplier.
     * 1–9 min → 0; otherwise the total lateness rounded up to whole hours.
     */
    public function chargeableLateHours(int $lateMinutes): int
    {
        if ($lateMinutes <= self::LATE_GRACE_MINUTES) {
            return 0;
        }

        return (int) ceil($lateMinutes / 60);
    }

    /** Late-entry deduction hours = chargeable hours × 2 (section 5). */
    public function lateDeductionHours(int $lateMinutes): int
    {
        return $this->chargeableLateHours($lateMinutes) * self::PENALTY_MULTIPLIER;
    }

    // ---- Early departure (sections 8–9) ----

    /** Chargeable early-departure hours before the multiplier (no grace). */
    public function chargeableEarlyHours(int $earlyMinutes): int
    {
        if ($earlyMinutes <= 0) {
            return 0;
        }

        return (int) ceil($earlyMinutes / 60);
    }

    /** Early-departure deduction hours = chargeable hours × 2 (section 8). */
    public function earlyDeductionHours(int $earlyMinutes): int
    {
        return $this->chargeableEarlyHours($earlyMinutes) * self::PENALTY_MULTIPLIER;
    }

    // ---- Missing punch (section 13) ----

    /** A flat 3 deduction hours per unapproved missing punch. */
    public function missingPunchHours(int $missingCount = 1): int
    {
        return max(0, $missingCount) * self::MISSING_PUNCH_HOURS;
    }

    // ---- Absence (sections 19–21) ----

    /**
     * Absence outcome for a day, keeping the three values the policy requires
     * separate (section 20): the actual absence fraction, the number of missed
     * shifts, and the payroll penalty days.
     *
     * @return array{absence_fraction: float, missed_shifts: int, penalty_days: int}
     */
    public function absence(int $requiredShifts, int $attendedShifts): array
    {
        $requiredShifts = max(0, $requiredShifts);
        $missed = max(0, min($requiredShifts, $requiredShifts - max(0, $attendedShifts)));

        // One-shift day (or rotating employee on a one-shift date, section 21).
        if ($requiredShifts <= 1) {
            $absent = $missed >= 1;

            return [
                'absence_fraction' => $absent ? 1.0 : 0.0,
                'missed_shifts' => $absent ? 1 : 0,
                'penalty_days' => $absent ? 1 : 0,
            ];
        }

        // Two-shift day: each missed shift is half a day and one penalty day.
        return [
            'absence_fraction' => $missed * 0.5,
            'missed_shifts' => $missed,
            'penalty_days' => $missed,
        ];
    }
}
