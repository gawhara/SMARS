<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'iso2',
        'name_ar',
        'name_en',
        'priority',
    ];

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Priority countries first (desc), then alphabetical by the active locale.
     */
    public function scopeOrdered($query)
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return $query->orderByDesc('priority')->orderBy($nameColumn);
    }
}
