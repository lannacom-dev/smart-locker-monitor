<?php

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Command;

/**
 * Runs all system health checks and generates/resolves alerts.
 *
 * Schedule: every 5 minutes
 *
 * Usage:
 *   php artisan health:check             # full run
 *   php artisan health:check --dry-run   # show what checks would produce (no DB writes)
 *   php artisan health:check --company=1 # check a single company only
 */
class RunHealthChecks extends Command
{
    protected $signature = 'health:check
                            {--dry-run   : Show check results without writing to database}
                            {--company=  : Only check a specific company ID}';

    protected $description = 'Run system health checks and generate/resolve alerts';

    public function __construct(private SystemHealthService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun    = (bool) $this->option('dry-run');
        $companyId = $this->option('company') ? (int) $this->option('company') : null;

        if ($dryRun) {
            return $this->runDryRun($companyId);
        }

        $this->info('Running system health checks…');

        $companyIds = $companyId ? [$companyId] : [];

        $results = $this->service->runAll($companyIds);

        $this->info(sprintf(
            'Done — %d companies | Device: %d | Connection: %d | API: %s | New alerts: %d | Resolved: %d',
            $results['companies_checked'],
            $results['device_checks'],
            $results['connection_checks'],
            $results['api_check'],
            $results['alerts_created'],
            $results['alerts_resolved'],
        ));

        if ($results['alerts_created'] > 0) {
            $this->warn("⚠  {$results['alerts_created']} new alert(s) raised.");
        }

        if ($results['alerts_resolved'] > 0) {
            $this->info("✓  {$results['alerts_resolved']} alert(s) auto-resolved.");
        }

        return self::SUCCESS;
    }

    private function runDryRun(?int $companyId): int
    {
        $this->warn('[DRY RUN] No data will be written to the database.');

        $companies = $companyId
            ? \App\Models\Company::where('id', $companyId)->get()
            : \App\Models\Company::where('is_active', true)->get();

        if ($companies->isEmpty()) {
            $this->error('No active companies found.');
            return self::FAILURE;
        }

        $headers = ['Company', 'Check Type', 'Status', 'Score', 'Key Metric'];
        $rows    = [];

        foreach ($companies as $company) {
            // Device health dry-run
            $deviceRows = \App\Models\Locker::where('company_id', $company->id)
                ->where('is_active', true)
                ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as cnt'))
                ->groupBy('status')
                ->pluck('cnt', 'status');

            $total     = (int) $deviceRows->sum();
            $fault     = (int) ($deviceRows[\App\Models\Locker::STATUS_FAULT] ?? 0);
            $faultRate = $total > 0 ? round($fault / $total * 100, 1) : 0;

            $deviceStatus = match (true) {
                $faultRate >= 20 => '🔴 CRITICAL',
                $faultRate >= 10 => '⚠ WARNING',
                default          => '✓ HEALTHY',
            };

            $rows[] = [$company->name, 'Device Health', $deviceStatus, (100 - (int) $faultRate) . '%', "Fault rate: {$faultRate}%"];

            // Connection health dry-run
            $connRows = \App\Models\Locker::where('company_id', $company->id)
                ->where('is_active', true)
                ->select('connection_status', \Illuminate\Support\Facades\DB::raw('count(*) as cnt'))
                ->groupBy('connection_status')
                ->pluck('cnt', 'connection_status');

            $connTotal  = (int) $connRows->sum();
            $online     = (int) ($connRows[\App\Models\LockerConnection::STATUS_ONLINE] ?? 0);
            $onlineRate = $connTotal > 0 ? round($online / $connTotal * 100, 1) : 100;

            $connStatus = match (true) {
                $onlineRate < 50 => '🔴 CRITICAL',
                $onlineRate < 70 => '⚠ WARNING',
                default          => '✓ HEALTHY',
            };

            $rows[] = [$company->name, 'Connection Health', $connStatus, (int) $onlineRate . '%', "Online rate: {$onlineRate}%"];
        }

        // API dry-run
        $apiUrl = config('services.smartlocker.base_url', '(not configured)');
        $rows[] = ['— Global —', 'API Health', '(not tested in dry-run)', '—', "Endpoint: {$apiUrl}"];

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}
