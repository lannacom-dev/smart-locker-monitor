<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Locker;
use App\Models\LockerConnection;
use App\Models\SystemAlert;
use App\Models\SystemHealthCheck;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs and stores periodic system health checks.
 *
 * Health Check Types:
 *   device_health     — locker operational status (fault/offline rates)
 *   connection_health — locker-to-server connectivity (online/warning/offline)
 *   api_health        — SmartLocker API (Lannacom) reachability
 *
 * Alert Types:
 *   locker_offline      — individual locker offline > threshold
 *   high_fault_rate     — company fault rate exceeds threshold
 *   connection_degraded — company online rate below threshold
 *   api_unreachable     — SmartLocker API cannot be reached
 */
class SystemHealthService
{
    // ── Thresholds ────────────────────────────────────────────────

    /** Locker must be offline for this many seconds before an alert is raised */
    private const OFFLINE_ALERT_SECONDS = 600;     // 10 minutes

    private const FAULT_RATE_WARNING  = 10.0;      // % of fault lockers → warning
    private const FAULT_RATE_CRITICAL = 20.0;      // % of fault lockers → critical

    private const ONLINE_RATE_WARNING  = 70.0;     // % online → warning if below
    private const ONLINE_RATE_CRITICAL = 50.0;     // % online → critical if below

    // ── Main entry point ──────────────────────────────────────────

    /**
     * Run all health checks for the given company IDs (or all companies).
     * Called by the scheduled command.
     *
     * @param  int[]  $companyIds  Company IDs to check; empty = all active companies
     */
    public function runAll(array $companyIds = []): array
    {
        if (empty($companyIds)) {
            $companyIds = Company::where('is_active', true)->pluck('id')->toArray();
        }

        $results = [
            'companies_checked' => count($companyIds),
            'device_checks'     => 0,
            'connection_checks' => 0,
            'api_check'         => false,
            'alerts_created'    => 0,
            'alerts_resolved'   => 0,
        ];

        foreach ($companyIds as $companyId) {
            $deviceCheck     = $this->checkDeviceHealth($companyId);
            $connectionCheck = $this->checkConnectionHealth($companyId);

            $results['device_checks']++;
            $results['connection_checks']++;

            // Auto-resolve alerts whose conditions have cleared
            $results['alerts_resolved'] += $this->autoResolveAlerts($companyId, $deviceCheck, $connectionCheck);

            // Generate new alerts for detected issues
            $results['alerts_created'] += $this->generateAlerts($companyId, $deviceCheck, $connectionCheck);
        }

        // API health (global, no company scope)
        $apiCheck = $this->checkApiHealth();
        $results['api_check'] = $apiCheck->status;
        $results['alerts_created'] += $this->handleApiAlerts($apiCheck);

        return $results;
    }

    // ── Health check runners ──────────────────────────────────────

    /**
     * Check the operational status of all lockers in a company.
     * Scores based on fault/offline rate.
     */
    public function checkDeviceHealth(int $companyId): SystemHealthCheck
    {
        $rows = Locker::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $total    = (int) $rows->sum();
        $fault    = (int) ($rows[Locker::STATUS_FAULT]    ?? 0);
        $offline  = (int) ($rows[Locker::STATUS_OFFLINE]  ?? 0);
        $disabled = (int) ($rows[Locker::STATUS_DISABLED] ?? 0);
        $available= (int) ($rows[Locker::STATUS_AVAILABLE]?? 0);
        $inUse    = (int) ($rows[Locker::STATUS_IN_USE]   ?? 0);

        $faultRate   = $total > 0 ? round($fault   / $total * 100, 1) : 0;
        $offlineRate = $total > 0 ? round($offline  / $total * 100, 1) : 0;
        $healthyRate = $total > 0 ? round(($available + $inUse) / $total * 100, 1) : 100;

        $score  = (int) max(0, $healthyRate);
        $status = match (true) {
            $faultRate >= self::FAULT_RATE_CRITICAL => SystemHealthCheck::STATUS_CRITICAL,
            $faultRate >= self::FAULT_RATE_WARNING  => SystemHealthCheck::STATUS_WARNING,
            default                                  => SystemHealthCheck::STATUS_HEALTHY,
        };

        return SystemHealthCheck::create([
            'company_id'  => $companyId,
            'check_type'  => SystemHealthCheck::TYPE_DEVICE,
            'status'      => $status,
            'score'       => $score,
            'details'     => compact(
                'total', 'available', 'inUse', 'fault', 'offline', 'disabled',
                'faultRate', 'offlineRate', 'healthyRate'
            ),
            'checked_at'  => now(),
        ]);
    }

    /**
     * Check the locker-to-server connectivity status.
     * Scores based on online/warning/offline ratio.
     */
    public function checkConnectionHealth(int $companyId): SystemHealthCheck
    {
        $rows = Locker::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('connection_status', DB::raw('count(*) as cnt'))
            ->groupBy('connection_status')
            ->pluck('cnt', 'connection_status');

        $total   = (int) $rows->sum();
        $online  = (int) ($rows[LockerConnection::STATUS_ONLINE]  ?? 0);
        $warning = (int) ($rows[LockerConnection::STATUS_WARNING] ?? 0);
        $offline = (int) ($rows[LockerConnection::STATUS_OFFLINE] ?? 0);

        $onlineRate  = $total > 0 ? round($online  / $total * 100, 1) : 100;
        $offlineRate = $total > 0 ? round($offline / $total * 100, 1) : 0;

        $score  = (int) $onlineRate;
        $status = match (true) {
            $onlineRate < self::ONLINE_RATE_CRITICAL => SystemHealthCheck::STATUS_CRITICAL,
            $onlineRate < self::ONLINE_RATE_WARNING  => SystemHealthCheck::STATUS_WARNING,
            default                                   => SystemHealthCheck::STATUS_HEALTHY,
        };

        return SystemHealthCheck::create([
            'company_id'  => $companyId,
            'check_type'  => SystemHealthCheck::TYPE_CONNECTION,
            'status'      => $status,
            'score'       => $score,
            'details'     => compact('total', 'online', 'warning', 'offline', 'onlineRate', 'offlineRate'),
            'checked_at'  => now(),
        ]);
    }

    /**
     * Check reachability of the Lannacom SmartLocker API.
     * Stored as a global check (company_id = null).
     */
    public function checkApiHealth(): SystemHealthCheck
    {
        $url   = rtrim((string) config('services.smartlocker.base_url', ''), '/');
        $start = microtime(true);

        $reachable  = false;
        $error      = null;
        $responseMs = null;

        if (empty($url)) {
            $error = 'SMARTLOCKER_API_URL not configured';
        } else {
            try {
                Http::withOptions(['verify' => false])
                    ->timeout(5)
                    ->get($url);

                $reachable  = true;
                $responseMs = (int) round((microtime(true) - $start) * 1000);
            } catch (Throwable $e) {
                $error      = $e->getMessage();
                $responseMs = (int) round((microtime(true) - $start) * 1000);
            }
        }

        $status = $reachable
            ? SystemHealthCheck::STATUS_HEALTHY
            : SystemHealthCheck::STATUS_CRITICAL;

        return SystemHealthCheck::create([
            'company_id'  => null,
            'check_type'  => SystemHealthCheck::TYPE_API,
            'status'      => $status,
            'score'       => $reachable ? 100 : 0,
            'details'     => [
                'reachable'        => $reachable,
                'endpoint'         => $url,
                'response_time_ms' => $responseMs,
                'error'            => $error,
            ],
            'checked_at'  => now(),
        ]);
    }

    // ── Alert management ──────────────────────────────────────────

    /**
     * Auto-resolve open alerts whose conditions have cleared.
     * Returns the number of alerts resolved.
     */
    public function autoResolveAlerts(int $companyId, SystemHealthCheck $device, SystemHealthCheck $connection): int
    {
        $resolved = 0;

        // Resolve high_fault_rate if fault rate is now acceptable
        if ($device->isHealthy()) {
            $resolved += $this->resolveOpenAlertByFingerprint(
                SystemAlert::makeFingerprint(SystemAlert::TYPE_HIGH_FAULT_RATE, (string) $companyId)
            );
        }

        // Resolve connection_degraded if online rate is now acceptable
        if ($connection->isHealthy()) {
            $resolved += $this->resolveOpenAlertByFingerprint(
                SystemAlert::makeFingerprint(SystemAlert::TYPE_CONNECTION_DEGRADED, (string) $companyId)
            );
        }

        // Resolve locker_offline alerts for lockers that came back online
        $backOnline = Locker::where('company_id', $companyId)
            ->where('connection_status', LockerConnection::STATUS_ONLINE)
            ->pluck('id');

        foreach ($backOnline as $lockerId) {
            $resolved += $this->resolveOpenAlertByFingerprint(
                SystemAlert::makeFingerprint(SystemAlert::TYPE_LOCKER_OFFLINE, (string) $lockerId)
            );
        }

        return $resolved;
    }

    /**
     * Generate new alerts for issues found in this check run.
     * Returns the number of new alerts created.
     */
    public function generateAlerts(int $companyId, SystemHealthCheck $device, SystemHealthCheck $connection): int
    {
        $created = 0;

        // ── High fault rate alert ─────────────────────────────────
        if (! $device->isHealthy()) {
            $fp = SystemAlert::makeFingerprint(SystemAlert::TYPE_HIGH_FAULT_RATE, (string) $companyId);
            if ($this->noOpenAlert($fp)) {
                $d          = $device->details ?? [];
                $faultRate  = $d['faultRate'] ?? 0;
                $severity   = $device->isCritical()
                    ? SystemAlert::SEV_CRITICAL
                    : SystemAlert::SEV_WARNING;

                SystemAlert::create([
                    'company_id'  => $companyId,
                    'alert_type'  => SystemAlert::TYPE_HIGH_FAULT_RATE,
                    'severity'    => $severity,
                    'title'       => "High Fault Rate Detected",
                    'message'     => "Fault rate is {$faultRate}% (threshold: " . self::FAULT_RATE_WARNING . "%). "
                                   . ($d['fault'] ?? 0) . " of " . ($d['total'] ?? 0) . " lockers are in fault state.",
                    'context'     => ['company_id' => $companyId, 'fault_rate' => $faultRate, 'details' => $d],
                    'fingerprint' => $fp,
                    'status'      => SystemAlert::STATUS_OPEN,
                ]);
                $created++;
            }
        }

        // ── Connection degraded alert ─────────────────────────────
        if (! $connection->isHealthy()) {
            $fp = SystemAlert::makeFingerprint(SystemAlert::TYPE_CONNECTION_DEGRADED, (string) $companyId);
            if ($this->noOpenAlert($fp)) {
                $d         = $connection->details ?? [];
                $onlineRate= $d['onlineRate'] ?? 0;
                $severity  = $connection->isCritical()
                    ? SystemAlert::SEV_CRITICAL
                    : SystemAlert::SEV_WARNING;

                SystemAlert::create([
                    'company_id'  => $companyId,
                    'alert_type'  => SystemAlert::TYPE_CONNECTION_DEGRADED,
                    'severity'    => $severity,
                    'title'       => "Connection Health Degraded",
                    'message'     => "Only {$onlineRate}% of lockers are online (threshold: " . self::ONLINE_RATE_WARNING . "%). "
                                   . ($d['offline'] ?? 0) . " offline, " . ($d['warning'] ?? 0) . " in warning state.",
                    'context'     => ['company_id' => $companyId, 'online_rate' => $onlineRate, 'details' => $d],
                    'fingerprint' => $fp,
                    'status'      => SystemAlert::STATUS_OPEN,
                ]);
                $created++;
            }
        }

        // ── Per-locker offline alerts ─────────────────────────────
        $offlineThreshold = Carbon::now()->subSeconds(self::OFFLINE_ALERT_SECONDS);

        $offlineLockers = Locker::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('connection_status', LockerConnection::STATUS_OFFLINE)
            ->where(function ($q) use ($offlineThreshold) {
                $q->whereNull('last_seen_at')
                  ->orWhere('last_seen_at', '<', $offlineThreshold);
            })
            ->get();

        foreach ($offlineLockers as $locker) {
            $fp = SystemAlert::makeFingerprint(SystemAlert::TYPE_LOCKER_OFFLINE, (string) $locker->id);
            if ($this->noOpenAlert($fp)) {
                $since = $locker->last_seen_at
                    ? $locker->last_seen_at->diffForHumans()
                    : 'never';

                SystemAlert::create([
                    'company_id'  => $companyId,
                    'alert_type'  => SystemAlert::TYPE_LOCKER_OFFLINE,
                    'severity'    => SystemAlert::SEV_CRITICAL,
                    'title'       => "Locker Offline: {$locker->name}",
                    'message'     => "Locker '{$locker->name}' (S/N: {$locker->serial_number}) has been offline since {$since}. "
                                   . "Last seen at " . ($locker->last_seen_at?->toDateTimeString() ?? 'never') . ".",
                    'context'     => [
                        'locker_id'     => $locker->id,
                        'locker_name'   => $locker->name,
                        'serial_number' => $locker->serial_number,
                        'last_seen_at'  => $locker->last_seen_at?->toIso8601String(),
                    ],
                    'fingerprint' => $fp,
                    'status'      => SystemAlert::STATUS_OPEN,
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Handle API-health alerts (global scope, company_id = null).
     */
    public function handleApiAlerts(SystemHealthCheck $apiCheck): int
    {
        $fp = SystemAlert::makeFingerprint(SystemAlert::TYPE_API_UNREACHABLE, 'global');

        if ($apiCheck->isCritical()) {
            if ($this->noOpenAlert($fp)) {
                $d = $apiCheck->details ?? [];
                SystemAlert::create([
                    'company_id'  => null,
                    'alert_type'  => SystemAlert::TYPE_API_UNREACHABLE,
                    'severity'    => SystemAlert::SEV_CRITICAL,
                    'title'       => 'SmartLocker API Unreachable',
                    'message'     => 'Cannot connect to the Lannacom SmartLocker API at '
                                   . ($d['endpoint'] ?? 'unknown') . '. '
                                   . 'Error: ' . ($d['error'] ?? 'unknown'),
                    'context'     => $d,
                    'fingerprint' => $fp,
                    'status'      => SystemAlert::STATUS_OPEN,
                ]);
                return 1;
            }
            return 0;
        }

        // API is reachable — auto-resolve any open api_unreachable alert
        return $this->resolveOpenAlertByFingerprint($fp);
    }

    // ── Alert actions ─────────────────────────────────────────────

    /**
     * Acknowledge an alert. Writes the audit record (acknowledged_by, acknowledged_at, note).
     */
    public function acknowledge(SystemAlert $alert, User $user, ?string $note = null): void
    {
        if (! $alert->isOpen()) {
            return;
        }

        $alert->update([
            'status'           => SystemAlert::STATUS_ACKNOWLEDGED,
            'acknowledged_by'  => $user->id,
            'acknowledged_at'  => now(),
            'acknowledge_note' => $note,
        ]);

        Log::info('system_alert.acknowledged', [
            'alert_id'    => $alert->id,
            'alert_type'  => $alert->alert_type,
            'acknowledged_by' => $user->id,
            'user_name'   => $user->name,
        ]);
    }

    // ── Query helpers (for Filament page) ────────────────────────

    /**
     * Get the latest health check per (company_id, check_type) for display.
     * Scoped to the user's accessible companies + global API check.
     */
    public function getLatestChecks(User $user, ?int $filterCompanyId = null): Collection
    {
        $companyIds = $this->resolveCompanyIds($user, $filterCompanyId);

        // We need the latest record per (company_id, check_type).
        // Use a simple approach: fetch all recent records and deduplicate in PHP.
        $cutoff = now()->subHours(2);   // Only show checks from the last 2 hours

        $checks = SystemHealthCheck::query()
            ->where(function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds)
                  ->orWhereNull('company_id');
            })
            ->where('checked_at', '>=', $cutoff)
            ->orderByDesc('checked_at')
            ->get();

        // Deduplicate: keep only the newest per (company_id, check_type)
        return $checks->unique(fn($c) => $c->company_id . '|' . $c->check_type);
    }

    /**
     * Get the overall system health status: 'critical' > 'warning' > 'healthy'
     * for the user's scope.
     */
    public function getOverallStatus(User $user, ?int $filterCompanyId = null): string
    {
        $checks = $this->getLatestChecks($user, $filterCompanyId);

        if ($checks->contains('status', SystemHealthCheck::STATUS_CRITICAL)) {
            return SystemHealthCheck::STATUS_CRITICAL;
        }

        if ($checks->contains('status', SystemHealthCheck::STATUS_WARNING)) {
            return SystemHealthCheck::STATUS_WARNING;
        }

        if ($checks->isEmpty()) {
            return 'unknown';
        }

        return SystemHealthCheck::STATUS_HEALTHY;
    }

    /**
     * Count open alerts by severity for the user's scope.
     */
    public function getIssueSummary(User $user, ?int $filterCompanyId = null): array
    {
        $companyIds = $this->resolveCompanyIds($user, $filterCompanyId);

        $rows = SystemAlert::where('status', SystemAlert::STATUS_OPEN)
            ->where(function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds)
                  ->orWhereNull('company_id');
            })
            ->select('severity', DB::raw('count(*) as cnt'))
            ->groupBy('severity')
            ->pluck('cnt', 'severity');

        return [
            SystemAlert::SEV_CRITICAL => (int) ($rows[SystemAlert::SEV_CRITICAL] ?? 0),
            SystemAlert::SEV_WARNING  => (int) ($rows[SystemAlert::SEV_WARNING]  ?? 0),
            SystemAlert::SEV_INFO     => (int) ($rows[SystemAlert::SEV_INFO]     ?? 0),
            'total'                   => (int) $rows->sum(),
        ];
    }

    /**
     * Return a paginated query builder for alerts, scoped to the user's access.
     */
    public function getAlertsQuery(
        User    $user,
        ?int    $filterCompanyId = null,
        ?string $filterStatus    = null,
        ?string $filterSeverity  = null
    ) {
        $companyIds = $this->resolveCompanyIds($user, $filterCompanyId);

        $query = SystemAlert::with(['company', 'acknowledgedBy'])
            ->where(function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds)
                  ->orWhereNull('company_id');
            })
            ->latestFirst();

        if ($filterStatus && $filterStatus !== '') {
            $query->where('status', $filterStatus);
        }

        if ($filterSeverity && $filterSeverity !== '') {
            $query->where('severity', $filterSeverity);
        }

        return $query;
    }

    // ── Internal utilities ────────────────────────────────────────

    private function noOpenAlert(string $fingerprint): bool
    {
        return ! SystemAlert::where('fingerprint', $fingerprint)
            ->where('status', SystemAlert::STATUS_OPEN)
            ->exists();
    }

    /** Resolve all open alerts matching fingerprint. Returns count resolved. */
    private function resolveOpenAlertByFingerprint(string $fingerprint): int
    {
        return SystemAlert::where('fingerprint', $fingerprint)
            ->where('status', SystemAlert::STATUS_OPEN)
            ->update([
                'status'      => SystemAlert::STATUS_RESOLVED,
                'resolved_at' => now(),
            ]);
    }

    /**
     * Resolve accessible company IDs for a user + optional filter.
     * Always includes null (for global alerts like API health).
     */
    private function resolveCompanyIds(User $user, ?int $filterCompanyId): array
    {
        if ($user->isSuperAdmin()) {
            return $filterCompanyId
                ? [$filterCompanyId]
                : Company::pluck('id')->toArray();
        }

        $ids = $user->accessibleCompanyIds();

        return $filterCompanyId && in_array($filterCompanyId, $ids, true)
            ? [$filterCompanyId]
            : $ids;
    }
}
