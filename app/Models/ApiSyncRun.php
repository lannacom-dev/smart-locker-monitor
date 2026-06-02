<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiSyncRun extends Model
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_RUNNING = 'running';

    protected $fillable = [
        'source',
        'status',
        'units_fetched',
        'lockers_updated',
        'lockers_skipped',
        'lockers_not_mapped',
        'error_message',
        'meta',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'meta'        => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public static function latestSuccess(?string $source = 'smartlocker'): ?self
    {
        return static::query()
            ->where('source', $source)
            ->where('status', self::STATUS_SUCCESS)
            ->orderByDesc('finished_at')
            ->first();
    }
}
