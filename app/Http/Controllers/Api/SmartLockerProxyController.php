<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Locker;
use App\Models\LockerEvent;
use App\Services\SmartLockerApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

/**
 * Proxies control commands to the Lannacom SmartLocker API.
 *
 * Authorization: the authenticated user must have access to the locker's company
 * and must hold an admin role (super_admin or tenant_admin).
 *
 * All actions are logged as LockerEvent entries for audit.
 */
class SmartLockerProxyController extends Controller
{
    public function __construct(private SmartLockerApiService $api) {}

    /**
     * POST /api/smartlocker/lockers/{locker}/unlock
     */
    public function unlock(Request $request, Locker $locker): JsonResponse
    {
        $this->authorizeLockerAccess($request, $locker);

        $unitId = $locker->external_unit_id;

        if (! $unitId) {
            return $this->noExternalId($locker);
        }

        try {
            $result = $this->api->unlockLocker($unitId);
        } catch (Throwable $e) {
            return $this->apiError('unlock', $e);
        }

        $this->logEvent($locker, 'unlock', [
            'source'        => 'admin_api_proxy',
            'triggered_by'  => $request->user()->id,
            'api_unit_id'   => $unitId,
            'api_response'  => $result,
        ]);

        return response()->json([
            'ok'      => true,
            'action'  => 'unlock',
            'locker'  => $locker->only(['id', 'name']),
            'api'     => $result,
        ]);
    }

    /**
     * POST /api/smartlocker/lockers/{locker}/emergency-unlock
     */
    public function emergencyUnlock(Request $request, Locker $locker): JsonResponse
    {
        $this->authorizeLockerAccess($request, $locker);

        $unitId = $locker->external_unit_id;

        if (! $unitId) {
            return $this->noExternalId($locker);
        }

        try {
            $result = $this->api->emergencyUnlock($unitId);
        } catch (Throwable $e) {
            return $this->apiError('emergency_unlock', $e);
        }

        $this->logEvent($locker, 'unlock', [
            'source'       => 'admin_api_proxy_emergency',
            'triggered_by' => $request->user()->id,
            'api_unit_id'  => $unitId,
            'api_response' => $result,
        ]);

        return response()->json([
            'ok'     => true,
            'action' => 'emergency_unlock',
            'locker' => $locker->only(['id', 'name']),
            'api'    => $result,
        ]);
    }

    /**
     * POST /api/smartlocker/lockers/{locker}/disable
     */
    public function disable(Request $request, Locker $locker): JsonResponse
    {
        $this->authorizeLockerAccess($request, $locker);

        $unitId = $locker->external_unit_id;

        if (! $unitId) {
            return $this->noExternalId($locker);
        }

        try {
            $result = $this->api->disableLocker($unitId);
        } catch (Throwable $e) {
            return $this->apiError('disable', $e);
        }

        $locker->update(['status' => Locker::STATUS_DISABLED]);

        $this->logEvent($locker, 'sync', [
            'source'       => 'admin_api_proxy_disable',
            'triggered_by' => $request->user()->id,
            'api_unit_id'  => $unitId,
            'new_status'   => Locker::STATUS_DISABLED,
            'api_response' => $result,
        ]);

        return response()->json([
            'ok'     => true,
            'action' => 'disable',
            'locker' => $locker->fresh()->only(['id', 'name', 'status']),
            'api'    => $result,
        ]);
    }

    /**
     * POST /api/smartlocker/lockers/{locker}/enable
     */
    public function enable(Request $request, Locker $locker): JsonResponse
    {
        $this->authorizeLockerAccess($request, $locker);

        $unitId = $locker->external_unit_id;

        if (! $unitId) {
            return $this->noExternalId($locker);
        }

        try {
            $result = $this->api->enableLocker($unitId);
        } catch (Throwable $e) {
            return $this->apiError('enable', $e);
        }

        $locker->update(['status' => Locker::STATUS_AVAILABLE]);

        $this->logEvent($locker, 'sync', [
            'source'       => 'admin_api_proxy_enable',
            'triggered_by' => $request->user()->id,
            'api_unit_id'  => $unitId,
            'new_status'   => Locker::STATUS_AVAILABLE,
            'api_response' => $result,
        ]);

        return response()->json([
            'ok'     => true,
            'action' => 'enable',
            'locker' => $locker->fresh()->only(['id', 'name', 'status']),
            'api'    => $result,
        ]);
    }

    /**
     * POST /api/smartlocker/sync
     * Trigger an on-demand sync (runs the artisan command in the background).
     */
    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->isTenantAdmin()) {
            abort(403, 'Only admins can trigger a sync.');
        }

        // Run the sync command asynchronously
        \Artisan::queue('smartlocker:sync');

        return response()->json([
            'ok'      => true,
            'message' => 'Sync job queued. Check logs/smartlocker-sync.log for progress.',
        ]);
    }

    // ── Internals ─────────────────────────────────────────────────

    private function authorizeLockerAccess(Request $request, Locker $locker): void
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->isTenantAdmin()) {
            throw new AuthorizationException('Only admins can perform locker control actions.');
        }

        if (! $user->canAccessCompany($locker->company_id)) {
            throw new AuthorizationException('You do not have access to this locker.');
        }
    }

    private function logEvent(Locker $locker, string $type, array $payload): void
    {
        LockerEvent::create([
            'company_id'    => $locker->company_id,
            'locker_id'     => $locker->id,
            'locker_box_id' => null,
            'event_type'    => $type,
            'payload'       => $payload,
        ]);
    }

    private function noExternalId(Locker $locker): JsonResponse
    {
        return response()->json([
            'ok'      => false,
            'message' => "Locker '{$locker->name}' has no external_unit_id mapped to Lannacom. Set it first.",
        ], 422);
    }

    private function apiError(string $action, Throwable $e): JsonResponse
    {
        return response()->json([
            'ok'      => false,
            'action'  => $action,
            'message' => 'Lannacom API error: ' . $e->getMessage(),
        ], 502);
    }
}
