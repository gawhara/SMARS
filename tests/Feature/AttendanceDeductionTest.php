<?php

namespace Tests\Feature;

use App\Services\Attendance\AttendanceDeductionService;
use Tests\TestCase;

/**
 * Verifies the deduction engine against the policy's decision tables and worked
 * examples (sections 6, 7, 9, 10, 13, 20).
 */
class AttendanceDeductionTest extends TestCase
{
    private AttendanceDeductionService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new AttendanceDeductionService();
    }

    /** Late-entry decision table + examples (sections 6–7). */
    public function test_late_entry_deduction_hours(): void
    {
        $cases = [
            0 => 0, 1 => 0, 8 => 0, 9 => 0,      // within the 9-min grace
            10 => 2, 45 => 2, 59 => 2, 60 => 2,  // 1 chargeable hour × 2
            61 => 4, 90 => 4, 119 => 4, 120 => 4,
            121 => 6, 130 => 6, 179 => 6, 180 => 6,
            181 => 8, 239 => 8,
        ];

        foreach ($cases as $minutes => $expected) {
            $this->assertSame($expected, $this->svc->lateDeductionHours($minutes), "late {$minutes} min");
        }
    }

    /** Early-departure decision table + examples (sections 9–10) — no grace. */
    public function test_early_departure_deduction_hours(): void
    {
        $cases = [
            0 => 0,
            1 => 2, 10 => 2, 30 => 2, 59 => 2, 60 => 2,
            61 => 4, 119 => 4, 120 => 4,
            121 => 6, 130 => 6, 180 => 6,
            181 => 8, 239 => 8,
        ];

        foreach ($cases as $minutes => $expected) {
            $this->assertSame($expected, $this->svc->earlyDeductionHours($minutes), "early {$minutes} min");
        }
    }

    /** Late entry and early departure are calculated separately, then added (section 12). */
    public function test_combined_late_and_early_are_added_separately(): void
    {
        // Arrives 30 late (→2) and leaves 30 early (→2): total 4, not one rounded 60→2.
        $this->assertSame(4, $this->svc->lateDeductionHours(30) + $this->svc->earlyDeductionHours(30));
    }

    /** Missing punch is a flat 3 hours each (section 13). */
    public function test_missing_punch_hours(): void
    {
        $this->assertSame(0, $this->svc->missingPunchHours(0));
        $this->assertSame(3, $this->svc->missingPunchHours(1));
        $this->assertSame(6, $this->svc->missingPunchHours(2));
    }

    /** One-shift absence (section 21). */
    public function test_one_shift_absence(): void
    {
        $this->assertSame(
            ['absence_fraction' => 0.0, 'missed_shifts' => 0, 'penalty_days' => 0],
            $this->svc->absence(requiredShifts: 1, attendedShifts: 1),
        );
        $this->assertSame(
            ['absence_fraction' => 1.0, 'missed_shifts' => 1, 'penalty_days' => 1],
            $this->svc->absence(requiredShifts: 1, attendedShifts: 0),
        );
    }

    /** Two-shift absence decision table (section 20). */
    public function test_two_shift_absence(): void
    {
        $this->assertSame(
            ['absence_fraction' => 0.0, 'missed_shifts' => 0, 'penalty_days' => 0],
            $this->svc->absence(2, 2),
        );
        $this->assertSame(
            ['absence_fraction' => 0.5, 'missed_shifts' => 1, 'penalty_days' => 1],
            $this->svc->absence(2, 1),
        );
        $this->assertSame(
            ['absence_fraction' => 1.0, 'missed_shifts' => 2, 'penalty_days' => 2],
            $this->svc->absence(2, 0),
        );
    }
}
