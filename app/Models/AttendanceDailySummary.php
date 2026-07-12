<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDailySummary extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'first_in_at' => 'datetime',
            'last_out_at' => 'datetime',
            'has_exception' => 'boolean',
            'exception_codes' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function localizedTime(string $attribute): string
    {
        $value = $this->{$attribute};
        if (! $value) {
            return __('app.none');
        }

        $time = $value instanceof Carbon ? $value : Carbon::parse($value);

        return app()->isLocale('ar')
            ? $time->format('h:i').' '.($time->format('A') === 'AM' ? __('app.time_am') : __('app.time_pm'))
            : $time->format('h:i A');
    }
}
