<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueStatusHistory extends Model
{
    // Immutable audit log
    const UPDATED_AT = null;

    protected $fillable = [
        'issue_id',
        'changed_by',
        'from_status',
        'to_status',
        'note',
        'metadata',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isReopened(): bool
    {
        return $this->to_status === Issue::STATUS_OPEN
            && in_array($this->from_status, [Issue::STATUS_RESOLVED, Issue::STATUS_CLOSED], true);
    }

    public function fromLabel(): string
    {
        return Issue::statusOptions()[$this->from_status] ?? ucfirst($this->from_status);
    }

    public function toLabel(): string
    {
        return Issue::statusOptions()[$this->to_status] ?? ucfirst($this->to_status);
    }

    public function source(): string
    {
        return $this->metadata['source'] ?? 'web';
    }
}
