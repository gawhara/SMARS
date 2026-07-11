<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRecord extends Model
{
    use HasFactory, SoftDeletes, TracksBlame;

    protected $fillable = [
        'employee_id',
        'attendance_machine_id',
        'device_user_id',
        'punch_at',
        'punch_type',
        'raw_punch_type',
        'verification_type',
        'source',
        'company_id',
        'branch_id',
        'sync_batch_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'punch_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(AttendanceMachine::class, 'attendance_machine_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AttendanceSyncBatch::class, 'sync_batch_id');
    }

    public function isMatched(): bool
    {
        return $this->employee_id !== null;
    }

    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->whereNull('employee_id');
    }

    public function scopeMatched(Builder $query): Builder
    {
        return $query->whereNotNull('employee_id');
    }
}
