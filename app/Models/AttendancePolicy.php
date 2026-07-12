<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePolicy extends Model
{
    use TracksBlame;

    protected $guarded = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['weekend_days' => 'array', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function defaults(?int $companyId = null): self
    {
        return new self([
            'company_id' => $companyId,
            'grace_minutes' => 10,
            'early_leave_grace_minutes' => 0,
            'full_day_minutes' => 480,
            'half_day_minutes' => 240,
            'overtime_after_minutes' => 480,
            'rounding_minutes' => 1,
            'weekend_days' => [5],
            'is_active' => true,
        ]);
    }
}
