<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An administrative / disciplinary penalty recorded against an employee. When
 * active with an amount, it is reflected as a deduction on that period's payroll.
 */
class AdministrativePenalty extends Model
{
    use SoftDeletes, TracksBlame;

    public const TYPES = ['warning', 'deduction', 'fine', 'suspension', 'other'];

    protected $fillable = [
        'employee_id',
        'penalty_date',
        'type',
        'reason',
        'amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'penalty_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
