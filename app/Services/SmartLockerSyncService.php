<?php

namespace App\Services;

use App\Models\ApiSyncRun;
use App\Models\Company;
use App\Models\Locker;
use App\Models\LockerEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmartLockerSyncService
{
    public function sync(bool $dryRun = false): ApiSyncRun
    {
        $run = ApiSyncRun::create([
            'source' => 'smartlocker',
            'status' => ApiSyncRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        $totalUnits = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalNotFound = 0;
        $perCompany = [];
        $hasFailure = false;

        $companies = Company::query()
            ->where('is_active', true)
            ->where('api_enabled', true)
            ->whereNotNull('api_base_url')
            ->where('api_base_url', '!=', '')
            ->orderBy('name')
            ->get();

        foreach ($companies as $company) {
            try {
                $api = SmartLockerApiService::forCompany($company);
                $raw = $api->getAllLockerUnits();
                $units = $this->normalizeUnitsList($raw);
                $result = $this->applyUnitsToDatabase($company, $units, $dryRun);

                $totalUnits += count($units);
                $totalUpdated += $result['updated'];
                $totalSkipped += $result['skipped'];
                $totalNotFound += $result['not_found'];

                $perCompany[] = [
                    'company_id' => $company->id,
                    'company_code' => $company->code,
                    'company_name' => $company->name,
                    'endpoint' => $company->api_base_url,
                    'units' => count($units),
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                    'not_found' => $result['not_found'],
                    'status' => 'success',
                ];
            } catch (Throwable $e) {
                $hasFailure = true;
                Log::error('SmartLocker per-company sync failed', [
                    'company_id' => $company->id,
                    'company_code' => $company->code,
                    'error' => $e->getMessage(),
                ]);

                $perCompany[] = [
                    'company_id' => $company->id,
                    'company_code' => $company->code,
                    'company_name' => $company->name,
                    'endpoint' => $company->api_base_url,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $run->update([
            'status' => $hasFailure ? ApiSyncRun::STATUS_FAILED : ApiSyncRun::STATUS_SUCCESS,
            'units_fetched' => $totalUnits,
            'lockers_updated' => $totalUpdated,
            'lockers_skipped' => $totalSkipped,
            'lockers_not_mapped' => $totalNotFound,
            'error_message' => $hasFailure ? 'One or more company syncs failed. Check meta.per_company.' : null,
            'finished_at' => now(),
            'meta' => [
                'dry_run' => $dryRun,
                'companies_attempted' => $companies->count(),
                'per_company' => $perCompany,
            ],
        ]);

        return $run->fresh();
    }

    public function normalizeUnitsList(array $response): array
    {
        if (isset($response['data']) && is_array($response['data'])) {
            return array_values($response['data']);
        }

        if (array_is_list($response)) {
            return $response;
        }

        if (isset($response['lockerUnitID']) || isset($response['id'])) {
            return [$response];
        }

        return [];
    }

    private function applyUnitsToDatabase(Company $company, array $units, bool $dryRun): array
    {
        $localLockers = Locker::query()
            ->where('company_id', $company->id)
            ->whereNotNull('external_unit_id')
            ->get()
            ->keyBy('external_unit_id');

        $updated = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($units as $unit) {
            $unitId = (int) ($unit['id'] ?? $unit['lockerUnitID'] ?? 0);

            if ($unitId === 0) {
                $skipped++;
                continue;
            }

            $locker = $localLockers->get($unitId);

            if ($locker === null) {
                $notFound++;
                continue;
            }

            $newStatus = $this->mapApiStatus($unit);

            if ($newStatus === null) {
                $skipped++;
                continue;
            }

            $oldStatus = $locker->status;

            if ($oldStatus === $newStatus) {
                $skipped++;
                continue;
            }

            if (! $dryRun) {
                $locker->update(['status' => $newStatus]);

                LockerEvent::create([
                    'company_id' => $locker->company_id,
                    'locker_id' => $locker->id,
                    'locker_box_id' => null,
                    'event_type' => LockerEvent::TYPE_SYNC,
                    'payload' => [
                        'source' => 'smartlocker_api_sync',
                        'company_code' => $company->code,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'api_unit_id' => $unitId,
                        'api_payload' => $unit,
                    ],
                ]);
            }

            $updated++;
        }

        return compact('updated', 'skipped', 'not_found');
    }

    private function mapApiStatus(array $unit): ?string
    {
        $enable = (bool) ($unit['enable'] ?? true);
        $cuStatus = (bool) ($unit['cuStatus'] ?? true);
        $hasItem = (bool) ($unit['has_item'] ?? false);

        if (! $enable) {
            return Locker::STATUS_DISABLED;
        }

        if (! $cuStatus) {
            return Locker::STATUS_FAULT;
        }

        return $hasItem ? Locker::STATUS_IN_USE : Locker::STATUS_AVAILABLE;
    }
}
