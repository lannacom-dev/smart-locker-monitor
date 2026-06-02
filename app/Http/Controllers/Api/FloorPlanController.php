<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FloorPlan;
use App\Models\Locker;
use App\Models\LockerConnection;
use App\Services\ConnectionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FloorPlanController extends Controller
{
    public function __construct(
        private readonly ConnectionStatusService $connectionService
    ) {}

    /**
     * GET /api/floor-plans
     * Returns floor plans accessible to the authenticated user.
     * Filters: company_id (super_admin), location_id, building, floor
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = FloorPlan::with('location')
            ->active()
            ->orderBy('building')
            ->orderBy('floor')
            ->orderBy('name');

        if (! $user->isSuperAdmin()) {
            $query->forCompany($user->company_id);
        } elseif ($request->filled('company_id')) {
            $query->forCompany((int) $request->company_id);
        }

        $query->filter([
            'building' => $request->building,
            'floor'    => $request->floor,
        ]);

        if ($request->filled('location_id')) {
            $query->forLocation((int) $request->location_id);
        }

        return response()->json($query->get());
    }

    /**
     * GET /api/floor-plans/{floorPlan}
     * Returns a single floor plan with all placed lockers and their current status.
     */
    public function show(Request $request, FloorPlan $floorPlan): JsonResponse
    {
        $this->authorizeFloorPlanAccess($request, $floorPlan);

        $connectionFilter = $request->string('connection_status')->toString() ?: null;
        $statusFilter     = $request->string('status')->toString() ?: null;
        $zoneFilter       = $request->string('zone')->toString() ?: null;

        $lockers = $this->connectionService
            ->lockersOnFloorPlan($floorPlan->id, $connectionFilter, $statusFilter, $zoneFilter)
            ->with(['location'])
            ->get()
            ->map(fn(Locker $locker) => [
                'id'                => $locker->id,
                'name'              => $locker->name,
                'serial_number'     => $locker->serial_number,
                'ip_address'        => $locker->ip_address,
                'status'            => $locker->status,
                'connection_status' => $locker->connection_status,
                'last_seen_at'      => $locker->last_seen_at?->toIso8601String(),
                'pos_x'             => (float) $locker->pos_x,
                'pos_y'             => (float) $locker->pos_y,
                'floor_zone'        => $locker->floor_zone,
                'floor_note'        => $locker->floor_note,
                'locker_location_id'=> $locker->locker_location_id,
                'conn_color'        => LockerConnection::statusHex()[$locker->connection_status] ?? '#94a3b8',
            ]);

        $summary = [
            'total'   => $lockers->count(),
            'online'  => $lockers->where('connection_status', LockerConnection::STATUS_ONLINE)->count(),
            'warning' => $lockers->where('connection_status', LockerConnection::STATUS_WARNING)->count(),
            'offline' => $lockers->where('connection_status', LockerConnection::STATUS_OFFLINE)->count(),
        ];

        return response()->json([
            'floor_plan' => $floorPlan->makeHidden(['company_id']),
            'lockers'    => $lockers,
            'summary'    => $summary,
        ]);
    }

    /**
     * GET /api/lockers/{locker}/connection-status
     * Returns the connection status + recent history for one locker.
     */
    public function lockerStatus(Request $request, Locker $locker): JsonResponse
    {
        $this->authorizeLockerAccess($request, $locker);

        $history = $locker->connections()
            ->latestFirst()
            ->limit(20)
            ->get(['old_status', 'new_status', 'source', 'reason', 'created_at']);

        return response()->json([
            'locker_id'         => $locker->id,
            'name'              => $locker->name,
            'connection_status' => $locker->connection_status,
            'last_seen_at'      => $locker->last_seen_at?->toIso8601String(),
            'heartbeat_interval'=> $locker->heartbeat_interval,
            'offline_after'     => $locker->offline_after,
            'history'           => $history,
        ]);
    }

    // ──────────────────────────────────────────────
    // Internal
    // ──────────────────────────────────────────────

    private function authorizeFloorPlanAccess(Request $request, FloorPlan $floorPlan): void
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $user->company_id !== $floorPlan->company_id) {
            abort(403, 'Access denied to this floor plan.');
        }
    }

    private function authorizeLockerAccess(Request $request, Locker $locker): void
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! $user->canAccessCompany($locker->company_id)) {
            abort(403, 'Access denied to this locker.');
        }
    }
}
