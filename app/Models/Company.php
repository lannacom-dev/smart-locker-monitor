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
        'api_base_url',
        'api_client_id',
        'api_client_secret',
        'api_timeout',
        'api_enabled',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'api_enabled'  => 'boolean',
        'api_timeout'  => 'integer',
    ];

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function childCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    public function descendantIds(): array
    {
        if (! $this->relationLoaded('childCompanies')) {
            $this->load('childCompanies');
        }

        $ids = [$this->id];

        foreach ($this->childCompanies as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

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
