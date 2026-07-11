<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSyncBatch extends Model
{
    protected $fillable = [
        'source',
        'file_name',
        'attendance_machine_id',
        'total_rows',
        'imported_count',
        'matched_count',
        'unmatched_count',
        'duplicate_count',
        'notes',
        'created_by',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(AttendanceMachine::class, 'attendance_machine_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'sync_batch_id');
    }
}
