<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LockerBox extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_OPEN = 'open';
    public const STATUS_ERROR = 'error';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'company_id',
        'locker_id',
        'box_number',
        'status',
        'last_opened_at',
        'is_active',
    ];

    protected $casts = [
        'last_opened_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LockerEvent::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isOccupied(): bool
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    public function markAvailable(): bool
    {
        return $this->update([
            'status' => self::STATUS_AVAILABLE,
        ]);
    }

    public function markOccupied(): bool
    {
        return $this->update([
            'status' => self::STATUS_OCCUPIED,
        ]);
    }

    public function markOpen(): bool
    {
        return $this->update([
            'status' => self::STATUS_OPEN,
            'last_opened_at' => now(),
        ]);
    }

    public function markError(): bool
    {
        return $this->update([
            'status' => self::STATUS_ERROR,
        ]);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', self::STATUS_OCCUPIED);
    }

    public function scopeError($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }
}
