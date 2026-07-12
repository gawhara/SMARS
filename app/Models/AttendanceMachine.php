<?php

namespace App\Models;

use App\Models\Concerns\TracksBlame;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceMachine extends Model
{
    use HasFactory, SoftDeletes, TracksBlame;

    protected $fillable = [
        'device_name',
        'device_model',
        'serial_number',
        'connection_type',
        'ip_address',
        'domain',
        'port',
        'comm_key',
        'username',
        'password',
        'company_id',
        'branch_id',
        'location_description',
        'timezone',
        'status',
        'is_active',
        'automatic_sync_enabled','sync_interval_minutes',
        'notes',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'automatic_sync_enabled' => 'boolean',
            'password' => 'encrypted',
            'last_sync_at' => 'datetime',
            'last_attendance_at' => 'datetime',
            'last_successful_connection_at' => 'datetime',
            'last_failed_connection_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(DeviceEnrollment::class, 'attendance_machine_id');
    }

    public function enrolledEmployees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'device_enrollments', 'attendance_machine_id', 'employee_id')
            ->withPivot('device_user_id', 'enrolled_at')
            ->withTimestamps();
    }

    /**
     * Host to reach the device on: DDNS uses the domain, everything else the IP.
     */
    public function host(): ?string
    {
        return $this->connection_type === 'ddns' ? $this->domain : $this->ip_address;
    }

    public function connectionTarget(): string
    {
        return $this->host() ? $this->host().':'.$this->port : '—';
    }

    /**
     * Effective status for display: inactive devices are shown as such regardless
     * of the last probe result.
     */
    public function displayStatus(): string
    {
        return $this->is_active ? ($this->status ?: 'unknown') : 'inactive';
    }
}
