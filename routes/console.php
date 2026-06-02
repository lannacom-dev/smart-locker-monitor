<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Jobs
|--------------------------------------------------------------------------
| Run  `php artisan schedule:run`  every minute.
| Windows Task Scheduler: every 1 min → php artisan schedule:run
| Linux cron:  * * * * * php artisan schedule:run >> /dev/null 2>&1
*/

// Detect stale heartbeats and update connection_status to WARNING / OFFLINE
Schedule::command('lockers:check-offline')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/offline-sweep.log'));

// System health checks — device, connection, API health + alert management
Schedule::command('health:check')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/health-check.log'));

// Sync locker status from the Lannacom SmartLocker API
// Only runs when SMARTLOCKER_CLIENT_ID is configured
Schedule::command('smartlocker:sync')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->when(fn() => ! empty(config('services.smartlocker.client_id')))
    ->appendOutputTo(storage_path('logs/smartlocker-sync.log'));
