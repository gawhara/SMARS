<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceHoliday extends Model
{
    use TracksBlame;
    protected $guarded = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at'];
    protected function casts(): array { return ['holiday_date' => 'date', 'is_paid' => 'boolean', 'is_active' => 'boolean']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function localizedName(): string { return app()->isLocale('ar') ? $this->name_ar : $this->name_en; }
}
