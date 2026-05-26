<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'parent_company_id',
        'name',
        'code',
        'contact_name',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Hierarchy ─────────────────────────────────────────────────

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function childCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /**
     * Recursively collect IDs of this company and all its descendants.
     * Used for hierarchical access control: a Nexastone admin can see
     * both Nexastone and Dynatix data.
     *
     * NOTE: eager-load `childCompanies` (and nested) before calling this
     * in a loop to avoid N+1 queries.
     */
    public function descendantIds(): array
    {
        // Ensure children are loaded so we don't fire a query per node
        if (! $this->relationLoaded('childCompanies')) {
            $this->load('childCompanies');
        }

        $ids = [$this->id];

        foreach ($this->childCompanies as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    // ── Relations ─────────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function lockers(): HasMany
    {
        return $this->hasMany(Locker::class);
    }

    public function lockerBoxes(): HasMany
    {
        return $this->hasMany(LockerBox::class);
    }

    public function lockerEvents(): HasMany
    {
        return $this->hasMany(LockerEvent::class);
    }
}
