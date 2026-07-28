<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named, savable late-entry deduction policy that can be assigned to employees.
 *
 * It varies the late-entry rule (policy sections 3–6): the grace threshold, the
 * whole-hour rounding, and the penalty multiplier. Absence and the money
 * conversion continue to use the shared engine and payroll config.
 */
class LatencyPolicy extends Model
{
    use HasFactory, TracksBlame;

    protected $guarded = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'grace_minutes' => 'integer',
            'round_up_to_hour' => 'boolean',
            'multiplier' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * The organisation default policy, or a transient one carrying the engine
     * defaults (9-min grace, round up, ×2) when none has been marked default.
     */
    public static function defaultPolicy(): self
    {
        return static::query()->where('is_default', true)->where('is_active', true)->first()
            ?? new self([
                'name' => __('app.latency.engine_default'),
                'grace_minutes' => 9,
                'round_up_to_hour' => true,
                'multiplier' => 2.00,
            ]);
    }

    /**
     * Chargeable late hours before the multiplier: 0 within grace, otherwise the
     * lateness rounded up to whole hours (or kept fractional when rounding off).
     */
    public function lateChargeableHours(int $lateMinutes): float
    {
        if ($lateMinutes <= (int) $this->grace_minutes) {
            return 0.0;
        }

        return $this->round_up_to_hour
            ? (float) ceil($lateMinutes / 60)
            : round($lateMinutes / 60, 4);
    }

    /** Deduction hours for a late entry = chargeable hours × multiplier. */
    public function lateDeductionHours(int $lateMinutes): float
    {
        return round($this->lateChargeableHours($lateMinutes) * (float) $this->multiplier, 4);
    }
}
