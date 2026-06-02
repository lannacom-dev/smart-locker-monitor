<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MaintenanceAttachment extends Model
{
    // Immutable — no updated_at
    const UPDATED_AT = null;

    // ── Phase constants ───────────────────────────────────────────
    public const PHASE_BEFORE = 'before';
    public const PHASE_DURING = 'during';
    public const PHASE_AFTER  = 'after';

    protected $fillable = [
        'maintenance_id',
        'uploaded_by',
        'phase',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size'       => 'integer',
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(CorrectiveMaintenance::class, 'maintenance_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** Public URL accessible via web. */
    public function url(): string
    {
        return Storage::url($this->file_path);
    }

    /** Human-readable file size. */
    public function formattedSize(): string
    {
        if (!$this->size) return '—';
        $kb = $this->size / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';
        return round($kb / 1024, 1) . ' MB';
    }

    public static function phaseOptions(): array
    {
        return [
            self::PHASE_BEFORE => 'Before',
            self::PHASE_DURING => 'During',
            self::PHASE_AFTER  => 'After',
        ];
    }
}
