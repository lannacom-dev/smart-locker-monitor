<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrectiveMaintenance extends Model
{
    // ── Status ────────────────────────────────────────────────────
    public const STATUS_CREATED     = 'created';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    // ── Type ──────────────────────────────────────────────────────
    public const TYPE_PREVENTIVE = 'preventive';
    public const TYPE_CORRECTIVE = 'corrective';
    public const TYPE_EMERGENCY  = 'emergency';

    // ── Priority ──────────────────────────────────────────────────
    public const PRIORITY_LOW    = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'company_id',
        'locker_id',
        'issue_id',
        'created_by',
        'technician_id',
        'title',
        'description',
        'root_cause',
        'solution',
        'notes',
        'status',
        'priority',
        'type',
        'scheduled_date',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'cost_estimate',
        'cost_actual',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'cancelled_at'   => 'datetime',
        'cost_estimate'  => 'decimal:2',
        'cost_actual'    => 'decimal:2',
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

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CorrectiveMaintenanceLog::class, 'maintenance_id')
                    ->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MaintenanceAttachment::class, 'maintenance_id')
                    ->orderBy('created_at');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeForCompanies($query, array $ids)
    {
        return count($ids) === 1
            ? $query->where('company_id', $ids[0])
            : $query->whereIn('company_id', $ids);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_CREATED, self::STATUS_IN_PROGRESS,
        ]);
    }

    public function scopeForTechnician($query, int $userId)
    {
        return $query->where('technician_id', $userId);
    }

    // ── Static Option Helpers ─────────────────────────────────────

    public static function typeOptions(): array
    {
        return [
            self::TYPE_PREVENTIVE => 'Preventive Maintenance',
            self::TYPE_CORRECTIVE => 'Corrective Maintenance',
            self::TYPE_EMERGENCY  => 'Emergency Maintenance',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? ucfirst($this->type ?? 'corrective');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_CREATED     => 'Created',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED   => 'Completed',
            self::STATUS_CANCELLED   => 'Cancelled',
        ];
    }

    public static function statusBadgeClasses(): array
    {
        return [
            self::STATUS_CREATED     => 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300 ring-sky-300/50',
            self::STATUS_IN_PROGRESS => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 ring-yellow-300/50',
            self::STATUS_COMPLETED   => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 ring-green-300/50',
            self::STATUS_CANCELLED   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 ring-gray-300/50',
        ];
    }

    public static function statusDotClasses(): array
    {
        return [
            self::STATUS_CREATED     => 'bg-sky-400',
            self::STATUS_IN_PROGRESS => 'bg-yellow-400',
            self::STATUS_COMPLETED   => 'bg-green-500',
            self::STATUS_CANCELLED   => 'bg-gray-400',
        ];
    }

    public static function statusButtonClasses(): array
    {
        return [
            self::STATUS_IN_PROGRESS => 'bg-yellow-50 text-yellow-700 border border-yellow-200 hover:bg-yellow-100 dark:bg-yellow-900/20 dark:text-yellow-300 dark:border-yellow-800',
            self::STATUS_COMPLETED   => 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800',
            self::STATUS_CANCELLED   => 'bg-gray-50 text-gray-600 border border-gray-200 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
            self::STATUS_CREATED     => 'bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW    => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH   => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public static function priorityBadgeClasses(): array
    {
        return [
            self::PRIORITY_LOW    => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            self::PRIORITY_MEDIUM => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            self::PRIORITY_HIGH   => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
            self::PRIORITY_URGENT => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        ];
    }

    public static function priorityBorderClasses(): array
    {
        return [
            self::PRIORITY_LOW    => 'border-l-4 border-gray-300',
            self::PRIORITY_MEDIUM => 'border-l-4 border-blue-400',
            self::PRIORITY_HIGH   => 'border-l-4 border-orange-400',
            self::PRIORITY_URGENT => 'border-l-4 border-red-500',
        ];
    }

    // ── Instance Helpers ──────────────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_CREATED, self::STATUS_IN_PROGRESS,
        ], true);
    }

    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst($this->status);
    }

    public function priorityLabel(): string
    {
        return self::priorityOptions()[$this->priority] ?? ucfirst($this->priority);
    }

    /** Duration from started_at to completed_at (or now if still running). */
    public function durationMinutes(): ?int
    {
        if (!$this->started_at) return null;
        $end = $this->completed_at ?? now();
        return (int) $this->started_at->diffInMinutes($end);
    }

    public function formattedDuration(): string
    {
        $mins = $this->durationMinutes();
        if ($mins === null) return '—';
        if ($mins < 60)  return "{$mins}m";
        $hours = intdiv($mins, 60);
        $rem   = $mins % 60;
        return $rem > 0 ? "{$hours}h {$rem}m" : "{$hours}h";
    }
}
