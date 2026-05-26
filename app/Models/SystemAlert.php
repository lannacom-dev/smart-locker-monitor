<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A system alert raised when a health check detects an issue.
 *
 * Alerts are deduplicated via the `fingerprint` column — only one open alert
 * exists per fingerprint at any time. Auto-resolved when the issue clears.
 *
 * alert_type: locker_offline | high_fault_rate | connection_degraded | api_unreachable
 * severity:   info | warning | critical
 * status:     open | acknowledged | resolved
 */
class SystemAlert extends Model
{
    // ── Alert types ───────────────────────────────────────────────

    public const TYPE_LOCKER_OFFLINE      = 'locker_offline';
    public const TYPE_HIGH_FAULT_RATE     = 'high_fault_rate';
    public const TYPE_CONNECTION_DEGRADED = 'connection_degraded';
    public const TYPE_API_UNREACHABLE     = 'api_unreachable';

    // ── Severity levels ───────────────────────────────────────────

    public const SEV_INFO     = 'info';
    public const SEV_WARNING  = 'warning';
    public const SEV_CRITICAL = 'critical';

    // ── Status ────────────────────────────────────────────────────

    public const STATUS_OPEN         = 'open';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED     = 'resolved';

    // ── Config ────────────────────────────────────────────────────

    protected $fillable = [
        'company_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'context',
        'fingerprint',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'acknowledge_note',
        'resolved_at',
    ];

    protected $casts = [
        'context'         => 'array',
        'acknowledged_at' => 'datetime',
        'resolved_at'     => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // ── State helpers ─────────────────────────────────────────────

    public function isOpen(): bool         { return $this->status === self::STATUS_OPEN; }
    public function isAcknowledged(): bool { return $this->status === self::STATUS_ACKNOWLEDGED; }
    public function isResolved(): bool     { return $this->status === self::STATUS_RESOLVED; }

    // ── Color maps ────────────────────────────────────────────────

    public static function severityColors(): array
    {
        return [
            self::SEV_INFO     => 'info',
            self::SEV_WARNING  => 'warning',
            self::SEV_CRITICAL => 'danger',
        ];
    }

    public static function severityIcons(): array
    {
        return [
            self::SEV_INFO     => 'heroicon-o-information-circle',
            self::SEV_WARNING  => 'heroicon-o-exclamation-triangle',
            self::SEV_CRITICAL => 'heroicon-o-x-circle',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_OPEN         => 'danger',
            self::STATUS_ACKNOWLEDGED => 'warning',
            self::STATUS_RESOLVED     => 'success',
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_LOCKER_OFFLINE      => 'Locker Offline',
            self::TYPE_HIGH_FAULT_RATE     => 'High Fault Rate',
            self::TYPE_CONNECTION_DEGRADED => 'Connection Degraded',
            self::TYPE_API_UNREACHABLE     => 'API Unreachable',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeForCompanies($query, array $companyIds)
    {
        return $query->where(function ($q) use ($companyIds) {
            $q->whereIn('company_id', $companyIds)
              ->orWhereNull('company_id');
        });
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    // ── Static fingerprint builder ────────────────────────────────

    public static function makeFingerprint(string $type, string $entityKey): string
    {
        return $type . ':' . $entityKey;
    }
}
