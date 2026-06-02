<?php

namespace App\Console\Commands;

use App\Services\SmartLockerSyncService;
use Illuminate\Console\Command;

class SyncSmartLockerStatus extends Command
{
    protected $signature = 'smartlocker:sync
                            {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Sync locker status from all enabled company API endpoints into the database';

    public function handle(SmartLockerSyncService $sync): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No DB writes.');
        }

        $this->info('Syncing from company APIs (DB config) ? database…');

        $run = $sync->sync($dryRun);

        $this->info(sprintf(
            'Done — units: %d | updated: %d | skipped: %d | not mapped: %d',
            $run->units_fetched,
            $run->lockers_updated,
            $run->lockers_skipped,
            $run->lockers_not_mapped,
        ));

        if ($run->status === 'failed') {
            $this->warn($run->error_message ?? 'Some company syncs failed.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
