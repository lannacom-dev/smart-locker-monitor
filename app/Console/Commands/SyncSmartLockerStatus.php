<?php

namespace App\Console\Commands;

use App\Models\Locker;
use App\Models\LockerEvent;
use App\Services\SmartLockerApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Syncs locker status from the Lannacom SmartLocker API into the local database.
 *
 * Matching is performed by `lockers.external_unit_id` ↔ the API's `lockerUnitID`.
 *
 * Usage:
 *   php artisan smartlocker:sync            # live sync
 *   php artisan smartlocker:sync --dry-run  # preview only (no DB writes)
 *   php artisan smartlocker:sync --ping     # test API connectivity and exit
 */
class SyncSmartLockerStatus extends Command
{
    protected $signature = 'smartlocker:sync
                            {--dry-run  : Preview changes without writing to the database}
                            {--ping     : Test API connectivity and exit}';

    protected $description = 'Sync locker status from the Lannacom SmartLocker API';

    public function __construct(private SmartLockerApiService $api)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // ── Ping mode ─────────────────────────────────────────────
        if ($this->option('ping')) {
            $this->info('Testing SmartLocker API connectivity…');

            if ($this->api->ping()) {
                $this->info('✓ Connected successfully — token obtained.');
                return self::SUCCESS;
            }

            $this->error('✗ Cannot reach SmartLocker API. Check SMARTLOCKER_CLIENT_ID / SECRET and URL.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes will be written to the database.');
        }

        // ── Fetch data from Lannacom ──────────────────────────────
        $this->info('Fetching locker units from Lannacom API…');

        try {
            $units = $this->api->getAllLockerUnits();
        } catch (Throwable $e) {
            $this->error('Failed to fetch locker units: ' . $e->getMessage());
            Log::error('smartlocker:sync — getAllLockerUnits failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        if (empty($units)) {
            $this->warn('API returned no locker units. Nothing to sync.');
            return self::SUCCESS;
        }

        $this->info('Received ' . count($units) . ' unit(s) from the API.');

        // ── Map external_unit_id → local Locker ───────────────────
        $localLockers = Locker::whereNotNull('external_unit_id')
            ->get()
            ->keyBy('external_unit_id');

        $updated   = 0;
        $skipped   = 0;
        $notFound  = 0;

        foreach ($units as $unit) {
            $unitId = (int) ($unit['id'] ?? $unit['lockerUnitID'] ?? 0);

            if ($unitId === 0) {
                $skipped++;
                continue;
            }

            /** @var Locker|null $locker */
            $locker = $localLockers->get($unitId);

            if ($locker === null) {
                $this->line("  [not found] external_unit_id={$unitId} — no local locker mapped");
                $notFound++;
                continue;
            }

            // Map Lannacom API status to our local status enum
            $newStatus = $this->mapApiStatus($unit);

            if ($newStatus === null) {
                $skipped++;
                continue;
            }

            $oldStatus = $locker->status;
            $changed   = $oldStatus !== $newStatus;

            if ($this->getOutput()->isVerbose()) {
                $this->line(sprintf(
                    '  [%s] %s (ext=%d): %s → %s',
                    $changed ? 'UPDATE' : 'same',
                    $locker->name,
                    $unitId,
                    $oldStatus,
                    $newStatus
                ));
            }

            if ($changed && ! $dryRun) {
                $locker->update(['status' => $newStatus]);

                LockerEvent::create([
                    'company_id'    => $locker->company_id,
                    'locker_id'     => $locker->id,
                    'locker_box_id' => null,
                    'event_type'    => LockerEvent::TYPE_SYNC,
                    'payload'       => [
                        'source'       => 'smartlocker_api_sync',
                        'old_status'   => $oldStatus,
                        'new_status'   => $newStatus,
                        'api_unit_id'  => $unitId,
                        'api_payload'  => $unit,
                    ],
                ]);

                $updated++;
            } elseif (! $changed) {
                $skipped++;
            }
        }

        $this->info(sprintf(
            'Sync complete — updated: %d | unchanged: %d | not mapped: %d',
            $updated,
            $skipped,
            $notFound,
        ));

        Log::info('smartlocker:sync completed', compact('updated', 'skipped', 'notFound', 'dryRun'));

        return self::SUCCESS;
    }

    /**
     * Map a Lannacom API unit record to a local status enum value.
     *
     * The API's `enable` flag overrides everything else:
     *   enable=false              → disabled
     *   cuStatus=false            → offline / fault
     *   enable=true, cuStatus=true → available or in_use based on has_item
     *
     * Returns null when the status cannot be determined (record is skipped).
     */
    private function mapApiStatus(array $unit): ?string
    {
        $enable    = (bool) ($unit['enable']    ?? true);
        $cuStatus  = (bool) ($unit['cuStatus']  ?? true);
        $hasItem   = (bool) ($unit['has_item']  ?? false);

        if (! $enable) {
            return Locker::STATUS_DISABLED;
        }

        if (! $cuStatus) {
            return Locker::STATUS_FAULT;
        }

        return $hasItem ? Locker::STATUS_IN_USE : Locker::STATUS_AVAILABLE;
    }
}
