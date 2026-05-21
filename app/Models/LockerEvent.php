<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LockerEvent extends Model
{
    public const TYPE_HEARTBEAT = 'heartbeat';
    public const TYPE_OPEN = 'open';
    public const TYPE_CLOSE = 'close';
    public const TYPE_UNLOCK = 'unlock';
    public const TYPE_ERROR = 'error';
    public const TYPE_SYNC = 'sync';

    protected $fillable = [
        'company_id',
        'locker_id',
        'locker_box_id',
        'event_type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(Locker::class);
    }

    public function lockerBox(): BelongsTo
    {
        return $this->belongsTo(LockerBox::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeHeartbeat($query)
    {
        return $query->where('event_type', self::TYPE_HEARTBEAT);
    }

    public function scopeUnlock($query)
    {
        return $query->where('event_type', self::TYPE_UNLOCK);
    }

    public function scopeError($query)
    {
        return $query->where('event_type', self::TYPE_ERROR);
    }

    public function scopeLatestFirst($query)
    {
        return $query->latest();
    }
}
