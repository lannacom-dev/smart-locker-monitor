<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockerLocation extends Model
{
    protected $fillable = [
        'company_id',
        'locker_id',
        'floor_plan_id',
        'pos_x',
        'pos_y',
        'zone',
        'note',
        'placed_by',
    ];

    protected $casts = [
        'pos_x' => 'decimal:3',
        'pos_y' => 'decimal:3',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(FloorPlan::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function placedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by');
    }
}
