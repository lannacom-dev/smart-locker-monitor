<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectiveMaintenanceLog extends Model
{
    // Immutable audit log
    const UPDATED_AT = null;

    // ── Action types ──────────────────────────────────────────────
    public const ACTION_CREATED             = 'created';
    public const ACTION_STATUS_CHANGED      = 'status_changed';
    public const ACTION_TECHNICIAN_ASSIGNED = 'technician_assigned';
    public const ACTION_ROOT_CAUSE_UPDATED  = 'root_cause_updated';
    public const ACTION_SOLUTION_UPDATED    = 'solution_updated';
    public const ACTION_COMPLETED           = 'completed';
    public const ACTION_CANCELLED           = 'cancelled';
    public const ACTION_REOPENED            = 'reopened';
    public const ACTION_REACTIVATED         = 'reactivated';
    public const ACTION_FIELD_UPDATED       = 'field_updated';
    public const ACTION_COST_UPDATED        = 'cost_updated';
    public const ACTION_NOTE_ADDED          = 'note_added';
    public const ACTION_ATTACHMENT_ADDED    = 'attachment_added';

    protected $fillable = [
        'maintenance_id',
        'changed_by',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(CorrectiveMaintenance::class, 'maintenance_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // ── Helpers ───────────────────────────────────────────────────

    public static function actionLabels(): array
    {
        return [
            self::ACTION_CREATED             => 'Created',
            self::ACTION_STATUS_CHANGED      => 'Status Changed',
            self::ACTION_TECHNICIAN_ASSIGNED => 'Technician Assigned',
            self::ACTION_ROOT_CAUSE_UPDATED  => 'Root Cause Updated',
            self::ACTION_SOLUTION_UPDATED    => 'Solution Updated',
            self::ACTION_COMPLETED           => 'Completed',
            self::ACTION_CANCELLED           => 'Cancelled',
            self::ACTION_REOPENED            => 'Reopened',
            self::ACTION_REACTIVATED         => 'Reactivated',
            self::ACTION_FIELD_UPDATED       => 'Field Updated',
            self::ACTION_COST_UPDATED        => 'Cost Updated',
            self::ACTION_NOTE_ADDED          => 'Note Added',
            self::ACTION_ATTACHMENT_ADDED    => 'Attachment Added',
        ];
    }

    public static function actionIcons(): array
    {
        return [
            self::ACTION_CREATED             => '🆕',
            self::ACTION_STATUS_CHANGED      => '🔄',
            self::ACTION_TECHNICIAN_ASSIGNED => '👤',
            self::ACTION_ROOT_CAUSE_UPDATED  => '🔍',
            self::ACTION_SOLUTION_UPDATED    => '🔧',
            self::ACTION_COMPLETED           => '✅',
            self::ACTION_CANCELLED           => '❌',
            self::ACTION_REOPENED            => '🔓',
            self::ACTION_REACTIVATED         => '♻️',
            self::ACTION_FIELD_UPDATED       => '✏️',
            self::ACTION_COST_UPDATED        => '💰',
            self::ACTION_NOTE_ADDED          => '📝',
            self::ACTION_ATTACHMENT_ADDED    => '📎',
        ];
    }

    public function actionLabel(): string
    {
        return self::actionLabels()[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    public function actionIcon(): string
    {
        return self::actionIcons()[$this->action] ?? '📝';
    }

    public function fieldLabel(): string
    {
        if (!$this->field_name) return '';
        return ucwords(str_replace('_', ' ', $this->field_name));
    }
}
