<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockerEditLog extends Model
{
    /** Immutable — no updated_at column */
    const UPDATED_AT = null;

    protected $fillable = [
        'locker_id',
        'changed_by',
        'field_name',
        'old_value',
        'new_value',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** Human-readable field labels for display */
    public static function fieldLabels(): array
    {
        return [
            'name'               => 'Name',
            'code'               => 'Code',
            'zone'               => 'Zone',
            'floor'              => 'Floor',
            'location_id'        => 'Location',
            'tenant_id'          => 'Tenant',
            'status'             => 'Status',
            'description'        => 'Description',
            'is_active'          => 'Active',
            'serial_number'      => 'Serial Number',
            'ip_address'         => 'IP Address',
            'firmware_version'   => 'Firmware Version',
            'heartbeat_interval' => 'Heartbeat Interval (s)',
            'offline_after'      => 'Offline After (s)',
        ];
    }

    public function fieldLabel(): string
    {
        return self::fieldLabels()[$this->field_name] ?? ucfirst(str_replace('_', ' ', $this->field_name));
    }

    /** Icon class for field type */
    public function fieldIcon(): string
    {
        return match ($this->field_name) {
            'status'             => 'heroicon-o-signal',
            'serial_number'      => 'heroicon-o-identification',
            'ip_address'         => 'heroicon-o-globe-alt',
            'location_id'        => 'heroicon-o-map-pin',
            'tenant_id'          => 'heroicon-o-building-office',
            'is_active'          => 'heroicon-o-power',
            default              => 'heroicon-o-pencil-square',
        };
    }
}
