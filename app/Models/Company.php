<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes, TracksBlame;

    protected $fillable = [
        'name_ar',
        'name_en',
        'legal_name_ar',
        'legal_name_en',
        'code',
        'cr_number',
        'vat_number',
        'phone',
        'email',
        'website',
        'city',
        'address',
        'established_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'established_date' => 'date',
        ];
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Resolve the company logo by convention: public/images/companies/{code}.png
     * (code lower-cased, underscores → hyphens). Returns null when no file exists,
     * so views can fall back to the initials mark.
     */
    public function logoUrl(): ?string
    {
        $file = 'images/companies/'.str_replace('_', '-', strtolower((string) $this->code)).'.png';

        return is_file(public_path($file)) ? asset($file) : null;
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

}
