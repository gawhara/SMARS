<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectionRequest extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'original_punch_at' => 'datetime',
            'requested_punch_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function attendanceRecord(): BelongsTo { return $this->belongsTo(AttendanceRecord::class); }
    public function replacementRecord(): BelongsTo { return $this->belongsTo(AttendanceRecord::class, 'replacement_record_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
