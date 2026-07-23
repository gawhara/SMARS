<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An immutable record of a sensitive action (payroll locks, biometric writes,
 * employee deletions, ...). Rows are written by AuditLogger and never updated.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'properties',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Localized label for the action code, falling back to the raw code when no
     * translation exists (e.g. an action added before its string was defined).
     */
    public function actionLabel(): string
    {
        $key = 'app.audit.act_'.str_replace('.', '_', $this->action);
        $label = __($key);

        return $label === $key ? $this->action : $label;
    }
}

