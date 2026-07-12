<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PayrollPeriod extends Model
{
    use TracksBlame;

    protected $fillable = [
        'company_id',
        'period_month',
        'status',
        'locked_at',
        'locked_by',
        'exported_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'locked_at' => 'datetime',
            'exported_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function label(): string
    {
        return Carbon::parse($this->period_month)->translatedFormat('F Y');
    }
}
