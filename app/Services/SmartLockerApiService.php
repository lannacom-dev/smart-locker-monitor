<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client wrapper for the Lannacom SmartLocker API.
 *
 * Base URL: https://message-service.lanna.co.th:5183
 * Auth:     OAuth2 ClientCredentials (POST /auth/token)
 *
 * Tokens are cached for 59 minutes (tokens typically expire in 1 hour).
 * Set SMARTLOCKER_CLIENT_ID and SMARTLOCKER_CLIENT_SECRET in .env to enable.
 */
class SmartLockerApiService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private int    $timeout;

    public function __construct()
    {
        $cfg = config('services.smartlocker');

        $this->baseUrl      = rtrim((string) ($cfg['base_url'] ?? 'https://message-service.lanna.co.th:5183'), '/');
        $this->clientId     = (string) ($cfg['client_id']     ?? '');
        $this->clientSecret = (string) ($cfg['client_secret'] ?? '');
        $this->timeout      = (int)    ($cfg['timeout']        ?? 10);
    }

    // ── Authentication ────────────────────────────────────────────

    /**
     * Get (or refresh) the access token, cached for 59 minutes.
     */
    public function getAccessToken(): string
    {
        return Cache::remember('smartlocker_access_token', 3540, function () {
            $response = Http::baseUrl($this->baseUrl)
                ->withOptions(['verify' => false])
                ->timeout($this->timeout)
                ->acceptJson()
                ->post('/auth/token', [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException(
                    'SmartLocker auth failed (' . $response->status() . '): ' . $response->body()
                );
            }

            $token = $response->json('access_token');

            if (! $token) {
                throw new RuntimeException('SmartLocker auth response missing access_token');
            }

            return $token;
        });
    }

    /**
     * Force-clear the cached token (e.g. after a 401 response).
     */
    public function forgetToken(): void
    {
        Cache::forget('smartlocker_access_token');
    }

    // ── Locker Data ───────────────────────────────────────────────

    /**
     * GET /init/get_all_locker_unit
     * Returns all locker units registered in the Lannacom system.
     */
    public function getAllLockerUnits(): array
    {
        return $this->authedGet('/init/get_all_locker_unit');
    }

    /**
     * GET /init/get_locker_unit
     * Returns a single locker unit.
     */
    public function getLockerUnit(int $unitId): array
    {
        return $this->authedGet('/init/get_locker_unit', ['id' => $unitId]);
    }

    /**
     * POST /locker/status
     * Returns current status of a specific locker unit.
     */
    public function getLockerStatus(int $lockerId, int $unitId): array
    {
        return $this->authedPost('/locker/status', [
            'lockerID'     => $lockerId,
            'lockerUnitID' => $unitId,
        ]);
    }

    /**
     * GET /init/get_use_record
     * Returns usage/transaction records. Supports optional filters.
     *
     * @param  array{lockerUnitId?: int, userId?: int, from?: string, to?: string}  $params
     */
    public function getUsageRecords(array $params = []): array
    {
        return $this->authedGet('/init/get_use_record', $params);
    }

    /**
     * GET /init/get_config
     * Returns system configuration from Lannacom.
     */
    public function getConfig(): array
    {
        return $this->authedGet('/init/get_config');
    }

    /**
     * GET /init/get_user_count
     */
    public function getUserCount(): array
    {
        return $this->authedGet('/init/get_user_count');
    }

    /**
     * GET /init/get_use_Heatmap
     * Returns usage heatmap data.
     */
    public function getUsageHeatmap(array $params = []): array
    {
        return $this->authedGet('/init/get_use_Heatmap', $params);
    }

    // ── Locker Control ────────────────────────────────────────────

    /**
     * POST /unlock_locker
     * Send an unlock command to a specific locker unit.
     */
    public function unlockLocker(int $unitId): array
    {
        return $this->authedPost('/unlock_locker', ['lockerUnitID' => $unitId]);
    }

    /**
     * POST /locker/emergency_unlock
     * Emergency unlock — bypasses normal booking state.
     */
    public function emergencyUnlock(int $unitId): array
    {
        return $this->authedPost('/locker/emergency_unlock', ['lockerUnitID' => $unitId]);
    }

    /**
     * POST /disable_locker
     * Disable a single locker unit.
     */
    public function disableLocker(int $unitId): array
    {
        return $this->authedPost('/disable_locker', ['lockerUnitID' => $unitId]);
    }

    /**
     * POST /enable_locker
     * Re-enable a single locker unit.
     */
    public function enableLocker(int $unitId): array
    {
        return $this->authedPost('/enable_locker', ['lockerUnitID' => $unitId]);
    }

    /**
     * POST /disable_whole_locker
     * Disable an entire locker cabinet.
     */
    public function disableWholeLocker(int $lockerId): array
    {
        return $this->authedPost('/disable_whole_locker', ['lockerID' => $lockerId]);
    }

    /**
     * POST /enable_whole_locker
     * Re-enable an entire locker cabinet.
     */
    public function enableWholeLocker(int $lockerId): array
    {
        return $this->authedPost('/enable_whole_locker', ['lockerID' => $lockerId]);
    }

    // ── Locker Status Update (device → server) ────────────────────

    /**
     * POST /Locker_status_update
     * Push a status update for a locker unit (typically called by the device firmware).
     */
    public function updateLockerStatus(array $payload): array
    {
        // Required: lockerUnitID, cuStatus, enable, is_read, has_item
        return $this->authedPost('/Locker_status_update', $payload);
    }

    // ── Logs ──────────────────────────────────────────────────────

    /**
     * GET /log/list
     * Fetch event logs with optional filters.
     *
     * @param  array{module?: string, status?: string, userId?: int, lockerUnitId?: int, from?: string, to?: string, page?: int, pageSize?: int}  $params
     */
    public function getLogs(array $params = []): array
    {
        return $this->authedGet('/log/list', $params);
    }

    /**
     * GET /log/{id}
     * Fetch a single log entry by ID.
     */
    public function getLog(int $id): array
    {
        return $this->authedGet("/log/{$id}");
    }

    // ── Connectivity check ────────────────────────────────────────

    /**
     * Verify that the API is reachable and credentials are valid.
     * Returns true on success, false on failure.
     */
    public function ping(): bool
    {
        try {
            $this->forgetToken();          // ensure fresh attempt
            $this->getAccessToken();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Internal helpers ──────────────────────────────────────────

    /**
     * Build an authenticated HTTP client instance.
     * Retries once after a 401 (token refresh).
     */
    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->getAccessToken())
            ->withOptions(['verify' => false])   // Lannacom uses a self-signed cert
            ->timeout($this->timeout)
            ->acceptJson();
    }

    private function authedGet(string $path, array $query = []): array
    {
        $response = $this->http()->get($path, $query);

        if ($response->status() === 401) {
            $this->forgetToken();
            $response = $this->http()->get($path, $query);
        }

        return $this->parseResponse($response, "GET {$path}");
    }

    private function authedPost(string $path, array $body = []): array
    {
        $response = $this->http()->post($path, $body);

        if ($response->status() === 401) {
            $this->forgetToken();
            $response = $this->http()->post($path, $body);
        }

        return $this->parseResponse($response, "POST {$path}");
    }

    private function parseResponse(Response $response, string $context): array
    {
        if (! $response->successful()) {
            throw new RuntimeException(
                "SmartLocker API error [{$context}] status={$response->status()}: " . $response->body()
            );
        }

        return (array) ($response->json() ?? []);
    }
}
