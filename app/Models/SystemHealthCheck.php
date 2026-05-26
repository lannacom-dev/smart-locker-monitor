<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot of a single health-check run for one (company, check_type) pair.
 *
 * check_type: device_health | connection_health | api_health
 * status:     healthy | warning | critical
 */
class SystemHealthCheck extends Model
{
    // ── Constants ─────────────────────────────────────────────────

    public const TYPE_DEVICE      = 'device_health';
    public const TYPE_CONNECTION  = 'connection_health';
    public const TYPE_API         = 'api_health';

    public const STATUS_HEALTHY  = 'healthy';
    public const STATUS_WARNING  = 'warning';
    public const STATUS_CRITICAL = 'critical';

    // ── Config ────────────────────────────────────────────────────

    protected $fillable = [
        'company_id',
        'check_type',
        'status',
        'score',
        'details',
        'checked_at',
    ];

    protected $casts = [
        'details'    => 'array',
        'checked_at' => 'datetime',
        'score'      => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isHealthy(): bool   { return $this->status === self::STATUS_HEALTHY; }
    public function isWarning(): bool   { return $this->status === self::STATUS_WARNING; }
    public function isCritical(): bool  { return $this->status === self::STATUS_CRITICAL; }

    /** Filament / Tailwind color token for this status */
    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_HEALTHY  => 'success',
            self::STATUS_WARNING  => 'warning',
            self::STATUS_CRITICAL => 'danger',
            default               => 'gray',
        };
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_HEALTHY  => 'success',
            self::STATUS_WARNING  => 'warning',
            self::STATUS_CRITICAL => 'danger',
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_DEVICE     => 'Device Health',
            self::TYPE_CONNECTION => 'Connection Health',
            self::TYPE_API        => 'API Health',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeLatestPerType($query)
    {
        // Returns the most-recent check for each (company_id, check_type) pair.
        // SQL Server compatible sub-query approach.
        return $query->orderByDesc('checked_at');
    }

    public function scopeForCompanies($query, array $companyIds)
    {
        return $query->whereIn('company_id', $companyIds)
                     ->orWhereNull('company_id');
    }
}
