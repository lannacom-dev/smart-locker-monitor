<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class FloorPlan extends Model
{
    protected $fillable = [
        'company_id',
        'location_id',
        'name',
        'building',
        'floor',
        'zone',
        'image_path',
        'image_url',
        'image_width',
        'image_height',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'image_width'  => 'integer',
        'image_height' => 'integer',
    ];

    protected $appends = ['display_image_url'];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** Positions of lockers placed on this floor plan */
    public function lockerPositions(): HasMany
    {
        return $this->hasMany(LockerLocation::class);
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    /** The URL to display the floor plan image (stored takes priority over external URL) */
    public function getDisplayImageUrlAttribute(): ?string
    {
        if ($this->image_path) {
            return Storage::url($this->image_path);
        }

        return $this->image_url;
    }

    /** Human-readable label e.g. "Building A – Floor 2 – Zone Server" */
    public function getLabelAttribute(): string
    {
        $parts = array_filter([
            $this->building,
            $this->floor ? 'Floor ' . $this->floor : null,
            $this->zone,
        ]);

        return $this->name . ($parts ? ' (' . implode(' · ', $parts) . ')' : '');
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when(isset($filters['building']) && $filters['building'] !== '',
                fn($q) => $q->where('building', $filters['building']))
            ->when(isset($filters['floor']) && $filters['floor'] !== '',
                fn($q) => $q->where('floor', $filters['floor']))
            ->when(isset($filters['zone']) && $filters['zone'] !== '',
                fn($q) => $q->where('zone', $filters['zone']));
    }
}
