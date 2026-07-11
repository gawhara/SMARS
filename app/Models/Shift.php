<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Shift extends Model
{
    use HasFactory, SoftDeletes, TracksBlame;

    protected $fillable = [
        'schedule_id',
        'shift_number',
        'schedule_name_ar',
        'name_ar',
        'name_en',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->schedule_name_ar ?: $this->name_ar)
            : __('app.shift').' '.$this->shift_number;
    }

    public function localizedTime(string $attribute): string
    {
        $time = Carbon::createFromFormat('H:i:s', (string) $this->{$attribute});

        if (app()->isLocale('ar')) {
            return $time->format('h:i').' '.($time->format('A') === 'AM' ? __('app.time_am') : __('app.time_pm'));
        }

        return $time->format('h:i A');
    }

    public function durationMinutes(): int
    {
        $start = Carbon::createFromFormat('H:i:s', (string) $this->start_time);
        $end = Carbon::createFromFormat('H:i:s', (string) $this->end_time);

        return (int) $start->diffInMinutes($end);
    }

}
