<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'preferred_locale',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'super_admin';
    }

    public function hasRole(string $name): bool
    {
        return $this->role?->name === $name;
    }

    /**
     * Permission names granted to this user (via its role), loaded once per
     * instance. Empty when the user has no role.
     *
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        return $this->permissionCache ??= $this->role
            ? $this->role->permissions()->pluck('name')
            : collect();
    }

    public function hasPermissionTo(string $name): bool
    {
        return $this->isSuperAdmin() || $this->permissionNames()->contains($name);
    }

    /** @var Collection<int, string>|null */
    protected ?Collection $permissionCache = null;
}
