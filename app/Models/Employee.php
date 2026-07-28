<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, TracksBlame;

    protected $guarded = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'iqama_expiry' => 'date',
            'passport_expiry' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'basic_salary' => 'decimal:2',
            'overtime' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'other_allowances' => 'decimal:2',
            'transportation_allowance' => 'decimal:2',
            'training_labor_wages' => 'decimal:2',
            'previous_dues' => 'decimal:2',
            'total' => 'decimal:2',
            'basic_salary_gosi' => 'decimal:2',
            'housing_allowance_gosi' => 'decimal:2',
            'other_gosi_items' => 'decimal:2',
            'diff_registered_housing_allowance' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'delay_deduction' => 'decimal:2',
            'leave_deduction' => 'decimal:2',
            'warnings_penalties' => 'decimal:2',
            'insurance_deduction' => 'decimal:2',
            'loans' => 'decimal:2',
            'social_insurance_saudi' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'cash' => 'decimal:2',
            'al_rajhi_transfer' => 'decimal:2',
            'bank_albilad_transfer' => 'decimal:2',
            'riyad_bank_transfer' => 'decimal:2',
            'remaining_salary' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Organizational branch. Named `orgBranch` because the `branch` column
     * (free-text bank branch, CODEX field 39) would otherwise shadow the relation.
     */
    public function orgBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function latencyPolicy(): BelongsTo
    {
        return $this->belongsTo(LatencyPolicy::class);
    }

    /**
     * The late-entry policy that applies to this employee: their assigned one,
     * or the organisation default when none is assigned.
     */
    public function effectiveLatencyPolicy(): LatencyPolicy
    {
        return $this->latencyPolicy && $this->latencyPolicy->is_active
            ? $this->latencyPolicy
            : LatencyPolicy::defaultPolicy();
    }

    public function attendanceDailySummaries(): HasMany
    {
        return $this->hasMany(AttendanceDailySummary::class);
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function isSaudi(): bool
    {
        return $this->saudi_non_saudi === 'saudi';
    }

    public function initials(): string
    {
        $name = trim((string) ($this->name_en ?: $this->name_ar));

        return mb_strtoupper(mb_substr($name, 0, 1)) ?: '?';
    }
}
