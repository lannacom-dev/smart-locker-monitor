<?php

namespace App\Console\Commands;

use App\Services\ConnectionStatusService;
use Illuminate\Console\Command;

/**
 * Sweep all active lockers and mark those with stale heartbeats as WARNING or OFFLINE.
 *
 * Schedule: every minute  →  php artisan lockers:check-offline
 *
 * Thresholds per locker (stored on the lockers row):
 *   heartbeat_interval  (default 60 s)  – expected ping frequency
 *   offline_after       (default 300 s) – silence → OFFLINE
 *   2× heartbeat_interval silence       → WARNING
 */
class CheckOfflineLockers extends Command
{
    protected $signature   = 'lockers:check-offline
                                {--dry-run : Show what would change without writing to DB}';

    protected $description = 'Detect lockers with stale heartbeats and update their connection_status';

    public function __construct(private readonly ConnectionStatusService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->dryRun();
            return self::SUCCESS;
        }

        $this->line('Checking for stale locker heartbeats...');

        $changed = $this->service->sweepStaleLockers();

        if ($changed === 0) {
            $this->info('All locker connection statuses are up to date.');
        } else {
            $this->warn("Updated connection status for {$changed} locker(s).");
        }

        return self::SUCCESS;
    }

    private function dryRun(): void
    {
        $this->info('[DRY RUN] Lockers that would be updated:');

        $headers = ['ID', 'Name', 'Last Seen', 'Current', 'Would Become'];
        $rows    = [];

        \App\Models\Locker::query()
            ->where('is_active', true)
            ->where('connection_status', '!=', \App\Models\LockerConnection::STATUS_OFFLINE)
            ->chunk(200, function ($lockers) use (&$rows) {
                foreach ($lockers as $locker) {
                    $computed = $locker->computeConnectionStatus();
                    if ($computed !== $locker->connection_status) {
                        $rows[] = [
                            $locker->id,
                            $locker->name,
                            $locker->last_seen_at?->diffForHumans() ?? 'never',
                            $locker->connection_status,
                            $computed,
                        ];
                    }
                }
            });

        if (empty($rows)) {
            $this->info('Nothing to change.');
        } else {
            $this->table($headers, $rows);
        }
    }
}
