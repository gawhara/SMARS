<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Automatically stamps the authenticated user into `created_by` / `updated_by`.
 *
 * Values are set directly on the model (bypassing mass assignment), so the
 * columns do not need to be listed in $fillable. Both tables using this concern
 * must have nullable `created_by` and `updated_by` columns.
 */
trait TracksBlame
{
    public static function bootTracksBlame(): void
    {
        static::creating(function ($model): void {
            if ($userId = Auth::id()) {
                $model->created_by ??= $userId;
                $model->updated_by ??= $userId;
            }
        });

        static::updating(function ($model): void {
            if ($userId = Auth::id()) {
                $model->updated_by = $userId;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
