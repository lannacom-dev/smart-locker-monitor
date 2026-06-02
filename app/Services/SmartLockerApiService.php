<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmartLockerApiService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private int $timeout;
    private string $cacheKey;

    public function __construct(?Company $company = null)
    {
        if ($company !== null) {
            $this->baseUrl = $this->normalizeBaseUrl((string) ($company->api_base_url ?? ''));
            $this->clientId = (string) ($company->api_client_id ?? '');
            $this->clientSecret = (string) ($company->api_client_secret ?? '');
            $this->timeout = (int) ($company->api_timeout ?? 10);
            $this->cacheKey = 'smartlocker_access_token_company_' . $company->id;

            if ($this->baseUrl === '') {
                throw new RuntimeException("Company '{$company->name}' has no API endpoint configured.");
            }

            return;
        }

        $cfg = config('services.smartlocker');
        $this->baseUrl = $this->normalizeBaseUrl((string) ($cfg['base_url'] ?? 'https://message-service.lanna.co.th:5183'));
        $this->clientId = (string) ($cfg['client_id'] ?? '');
        $this->clientSecret = (string) ($cfg['client_secret'] ?? '');
        $this->timeout = (int) ($cfg['timeout'] ?? 10);
        $this->cacheKey = 'smartlocker_access_token_default';
    }

    public static function forCompany(Company $company): self
    {
        return new self($company);
    }

    public function getAccessToken(): string
    {
        return Cache::remember($this->cacheKey, 3540, function () {
            $response = Http::baseUrl($this->baseUrl)
                ->withOptions(['verify' => false])
                ->timeout($this->timeout)
                ->acceptJson()
                ->post('/auth/token', [
                    'client_id' => $this->clientId,
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

    public function forgetToken(): void
    {
        Cache::forget($this->cacheKey);
    }

    public function getAllLockerUnits(): array
    {
        return $this->authedGet('/init/get_all_locker_unit');
    }

    public function getLockerUnit(int $unitId): array
    {
        return $this->authedGet('/init/get_locker_unit', ['id' => $unitId]);
    }

    public function getLockerStatus(int $lockerId, int $unitId): array
    {
        return $this->authedPost('/locker/status', [
            'lockerID' => $lockerId,
            'lockerUnitID' => $unitId,
        ]);
    }

    public function getUsageRecords(array $params = []): array
    {
        return $this->authedGet('/init/get_use_record', $params);
    }

    public function getConfig(): array
    {
        return $this->authedGet('/init/get_config');
    }

    public function getUserCount(): array
    {
        return $this->authedGet('/init/get_user_count');
    }

    public function getUsageHeatmap(array $params = []): array
    {
        return $this->authedGet('/init/get_use_Heatmap', $params);
    }

    public function unlockLocker(int $unitId): array
    {
        return $this->authedPost('/unlock_locker', ['lockerUnitID' => $unitId]);
    }

    public function emergencyUnlock(int $unitId): array
    {
        return $this->authedPost('/locker/emergency_unlock', ['lockerUnitID' => $unitId]);
    }

    public function disableLocker(int $unitId): array
    {
        return $this->authedPost('/disable_locker', ['lockerUnitID' => $unitId]);
    }

    public function enableLocker(int $unitId): array
    {
        return $this->authedPost('/enable_locker', ['lockerUnitID' => $unitId]);
    }

    public function disableWholeLocker(int $lockerId): array
    {
        return $this->authedPost('/disable_whole_locker', ['lockerID' => $lockerId]);
    }

    public function enableWholeLocker(int $lockerId): array
    {
        return $this->authedPost('/enable_whole_locker', ['lockerID' => $lockerId]);
    }

    public function updateLockerStatus(array $payload): array
    {
        return $this->authedPost('/Locker_status_update', $payload);
    }

    public function getLogs(array $params = []): array
    {
        return $this->authedGet('/log/list', $params);
    }

    public function getLog(int $id): array
    {
        return $this->authedGet("/log/{$id}");
    }

    public function ping(): bool
    {
        try {
            $this->forgetToken();
            $this->getAccessToken();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->getAccessToken())
            ->withOptions(['verify' => false])
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

    private function normalizeBaseUrl(string $url): string
    {
        $clean = trim($url);
        if ($clean === '') {
            return '';
        }

        $clean = str_replace('/:', ':', $clean);
        $clean = preg_replace('#(?<!:)/{2,}#', '/', $clean) ?? $clean;

        return rtrim($clean, '/');
    }
}
