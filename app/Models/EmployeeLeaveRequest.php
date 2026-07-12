<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveRequest extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected function casts(): array { return ['start_date' => 'date', 'end_date' => 'date', 'reviewed_at' => 'datetime']; }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
