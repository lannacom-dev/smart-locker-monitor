<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueAssignment extends Model
{
    // Audit log — immutable, no updated_at
    const UPDATED_AT = null;

    // ── Event types ───────────────────────────────────────────────
    public const TYPE_CREATED        = 'created';
    public const TYPE_ASSIGNED       = 'assigned';
    public const TYPE_UNASSIGNED     = 'unassigned';
    public const TYPE_STATUS_CHANGED = 'status_changed';
    public const TYPE_RESOLVED       = 'resolved';
    public const TYPE_CLOSED         = 'closed';
    public const TYPE_REOPENED       = 'reopened';

    protected $fillable = [
        'issue_id',
        'type',
        'performed_by',
        'assigned_to',
        'old_value',
        'new_value',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ── Helpers ───────────────────────────────────────────────────

    public static function typeLabels(): array
    {
        return [
            self::TYPE_CREATED        => 'Created',
            self::TYPE_ASSIGNED       => 'Assigned',
            self::TYPE_UNASSIGNED     => 'Unassigned',
            self::TYPE_STATUS_CHANGED => 'Status Changed',
            self::TYPE_RESOLVED       => 'Resolved',
            self::TYPE_CLOSED         => 'Closed',
            self::TYPE_REOPENED       => 'Reopened',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? ucfirst($this->type);
    }

    public static function typeIcons(): array
    {
        return [
            self::TYPE_CREATED        => '🆕',
            self::TYPE_ASSIGNED       => '👤',
            self::TYPE_UNASSIGNED     => '➖',
            self::TYPE_STATUS_CHANGED => '🔄',
            self::TYPE_RESOLVED       => '✅',
            self::TYPE_CLOSED         => '🔒',
            self::TYPE_REOPENED       => '🔓',
        ];
    }

    public function typeIcon(): string
    {
        return self::typeIcons()[$this->type] ?? '📝';
    }
}
