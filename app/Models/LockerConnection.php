<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockerConnection extends Model
{
    public const STATUS_ONLINE  = 'online';
    public const STATUS_WARNING = 'warning';
    public const STATUS_OFFLINE = 'offline';

    /** Immutable audit log – no updated_at */
    public const UPDATED_AT = null;

    public const SOURCE_HEARTBEAT = 'heartbeat';
    public const SOURCE_COMMAND   = 'command';
    public const SOURCE_API       = 'api';
    public const SOURCE_MANUAL    = 'manual';
    public const SOURCE_SYSTEM    = 'system';

    protected $fillable = [
        'company_id',
        'locker_id',
        'old_status',
        'new_status',
        'source',
        'reason',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeForLocker($query, int $lockerId)
    {
        return $query->where('locker_id', $lockerId);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('created_at');
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ONLINE  => 'Online',
            self::STATUS_WARNING => 'Warning',
            self::STATUS_OFFLINE => 'Offline',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_ONLINE  => 'success',
            self::STATUS_WARNING => 'warning',
            self::STATUS_OFFLINE => 'danger',
        ];
    }

    /** Hex colours used on the floor plan SVG markers */
    public static function statusHex(): array
    {
        return [
            self::STATUS_ONLINE  => '#22c55e',
            self::STATUS_WARNING => '#f59e0b',
            self::STATUS_OFFLINE => '#ef4444',
        ];
    }
}
