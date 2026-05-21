<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'code',
        'contact_name',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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