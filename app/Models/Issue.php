<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    // ── Status ────────────────────────────────────────────────────
    public const STATUS_OPEN        = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PENDING     = 'pending';
    public const STATUS_RESOLVED    = 'resolved';
    public const STATUS_CLOSED      = 'closed';

    // ── Severity ──────────────────────────────────────────────────
    public const SEV_LOW      = 'low';
    public const SEV_MEDIUM   = 'medium';
    public const SEV_HIGH     = 'high';
    public const SEV_CRITICAL = 'critical';

    // ── Category ─────────────────────────────────────────────────
    public const CAT_HARDWARE = 'hardware';
    public const CAT_SOFTWARE = 'software';
    public const CAT_NETWORK  = 'network';
    public const CAT_POWER    = 'power';
    public const CAT_OTHER    = 'other';

    protected $fillable = [
        'company_id',
        'locker_id',
        'reported_by',
        'assigned_to',
        'title',
        'description',
        'category',
        'severity',
        'status',
        'due_date',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'due_date'    => 'date',
        'resolved_at' => 'datetime',
        'closed_at'   => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IssueComment::class)->orderBy('created_at');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(IssueAssignment::class)->orderBy('created_at');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(IssueStatusHistory::class)->orderBy('created_at');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeForCompanies($query, array $ids)
    {
        return count($ids) === 1
            ? $query->where('company_id', $ids[0])
            : $query->whereIn('company_id', $ids);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeOfSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    // ── Static Option Helpers ─────────────────────────────────────

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN        => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PENDING     => 'Pending',
            self::STATUS_RESOLVED    => 'Resolved',
            self::STATUS_CLOSED      => 'Closed',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_OPEN        => 'blue',
            self::STATUS_IN_PROGRESS => 'yellow',
            self::STATUS_PENDING     => 'amber',
            self::STATUS_RESOLVED    => 'green',
            self::STATUS_CLOSED      => 'gray',
        ];
    }

    /** Tailwind CSS classes for each status badge */
    public static function statusBadgeClasses(): array
    {
        return [
            self::STATUS_OPEN        => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 ring-blue-300/50',
            self::STATUS_IN_PROGRESS => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 ring-yellow-300/50',
            self::STATUS_PENDING     => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 ring-amber-300/50',
            self::STATUS_RESOLVED    => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 ring-green-300/50',
            self::STATUS_CLOSED      => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 ring-gray-300/50',
        ];
    }

    /** Button background when selecting this status as the next target */
    public static function statusButtonClasses(): array
    {
        return [
            self::STATUS_OPEN        => 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
            self::STATUS_IN_PROGRESS => 'bg-yellow-50 text-yellow-700 border border-yellow-200 hover:bg-yellow-100 dark:bg-yellow-900/20 dark:text-yellow-300 dark:border-yellow-800',
            self::STATUS_PENDING     => 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
            self::STATUS_RESOLVED    => 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800',
            self::STATUS_CLOSED      => 'bg-gray-50 text-gray-600 border border-gray-200 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
        ];
    }

    /** Left-border accent for issue rows */
    public static function statusBorderClasses(): array
    {
        return [
            self::STATUS_OPEN        => 'border-l-4 border-blue-400',
            self::STATUS_IN_PROGRESS => 'border-l-4 border-yellow-400',
            self::STATUS_PENDING     => 'border-l-4 border-amber-400',
            self::STATUS_RESOLVED    => 'border-l-4 border-green-400',
            self::STATUS_CLOSED      => 'border-l-4 border-gray-300',
        ];
    }

    /** Dot colors for timeline markers */
    public static function statusDotClasses(): array
    {
        return [
            self::STATUS_OPEN        => 'bg-blue-400',
            self::STATUS_IN_PROGRESS => 'bg-yellow-400',
            self::STATUS_PENDING     => 'bg-amber-400',
            self::STATUS_RESOLVED    => 'bg-green-500',
            self::STATUS_CLOSED      => 'bg-gray-400',
        ];
    }

    public function statusColor(): string
    {
        return self::statusColors()[$this->status] ?? 'gray';
    }

    public static function severityOptions(): array
    {
        return [
            self::SEV_LOW      => 'Low',
            self::SEV_MEDIUM   => 'Medium',
            self::SEV_HIGH     => 'High',
            self::SEV_CRITICAL => 'Critical',
        ];
    }

    public static function severityColors(): array
    {
        return [
            self::SEV_LOW      => 'gray',
            self::SEV_MEDIUM   => 'blue',
            self::SEV_HIGH     => 'orange',
            self::SEV_CRITICAL => 'red',
        ];
    }

    public function severityColor(): string
    {
        return self::severityColors()[$this->severity] ?? 'gray';
    }

    public static function categoryOptions(): array
    {
        return [
            self::CAT_HARDWARE => 'Hardware',
            self::CAT_SOFTWARE => 'Software',
            self::CAT_NETWORK  => 'Network',
            self::CAT_POWER    => 'Power',
            self::CAT_OTHER    => 'Other',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_PENDING,
        ], true);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst($this->status);
    }

    public function severityLabel(): string
    {
        return self::severityOptions()[$this->severity] ?? ucfirst($this->severity);
    }

    public function categoryLabel(): string
    {
        return self::categoryOptions()[$this->category] ?? ucfirst($this->category);
    }
}
