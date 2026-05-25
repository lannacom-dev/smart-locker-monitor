<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Locker extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_IN_USE    = 'in_use';
    public const STATUS_FAULT     = 'fault';
    public const STATUS_OFFLINE   = 'offline';
    public const STATUS_DISABLED  = 'disabled';

    protected $fillable = [
        'company_id',
        'location_id',
        'name',
        'serial_number',
        'api_token',
        'ip_address',
        'status',
        'last_seen_at',
        'firmware_version',
        'description',
        'is_active',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_token',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(LockerBox::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LockerEvent::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(LockerStatusLog::class);
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subMinutes(2));
    }

    public function isOffline(): bool
    {
        return $this->status === self::STATUS_OFFLINE;
    }

    public function markAvailable(): bool
    {
        return $this->update(['status' => self::STATUS_AVAILABLE, 'last_seen_at' => now()]);
    }

    public function markInUse(): bool
    {
        return $this->update(['status' => self::STATUS_IN_USE]);
    }

    public function markFault(): bool
    {
        return $this->update(['status' => self::STATUS_FAULT]);
    }

    public function markOffline(): bool
    {
        return $this->update(['status' => self::STATUS_OFFLINE]);
    }

    public function markDisabled(): bool
    {
        return $this->update(['status' => self::STATUS_DISABLED]);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_IN_USE    => 'In Use',
            self::STATUS_FAULT     => 'Fault',
            self::STATUS_OFFLINE   => 'Offline',
            self::STATUS_DISABLED  => 'Disabled',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_IN_USE    => 'info',
            self::STATUS_FAULT     => 'danger',
            self::STATUS_OFFLINE   => 'gray',
            self::STATUS_DISABLED  => 'warning',
        ];
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes(2));
    }

    public function scopeOffline($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_seen_at')
                ->orWhere('last_seen_at', '<', now()->subMinutes(2));
        });
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}