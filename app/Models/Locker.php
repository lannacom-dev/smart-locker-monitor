<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Locker extends Model
{
    public const STATUS_ONLINE = 'online';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_ERROR = 'error';

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

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subMinutes(2));
    }

    public function isOffline(): bool
    {
        return !$this->isOnline();
    }

    public function markOnline(): bool
    {
        return $this->update([
            'status' => self::STATUS_ONLINE,
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): bool
    {
        return $this->update([
            'status' => self::STATUS_OFFLINE,
        ]);
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