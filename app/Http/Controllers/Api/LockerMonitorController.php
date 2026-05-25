<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLockerStatusRequest;
use App\Models\Locker;
use App\Services\LockerStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LockerMonitorController extends Controller
{
    public function __construct(private readonly LockerStatusService $service) {}

    /**
     * GET /api/lockers
     *
     * Query params: company_id (super_admin only), location_id, status, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->service->getFilteredQuery(
            user:       $request->user(),
            locationId: $request->integer('location_id') ?: null,
            status:     $request->string('status')->toString() ?: null,
            companyId:  $request->integer('company_id') ?: null,
        );

        $lockers = $query
            ->with(['statusLogs' => fn($q) => $q->latestFirst()->limit(1)])
            ->paginate($request->integer('per_page', 15));

        return response()->json($lockers);
    }

    /**
     * PATCH /api/lockers/{locker}/status
     *
     * Body: { status: string, reason?: string }
     */
    public function updateStatus(UpdateLockerStatusRequest $request, Locker $locker): JsonResponse
    {
        $log = $this->service->updateStatus(
            locker:    $locker,
            newStatus: $request->string('status')->toString(),
            changedBy: $request->user(),
            reason:    $request->string('reason')->toString() ?: null,
        );

        return response()->json([
            'locker' => $locker->fresh(['company', 'location']),
            'log'    => $log,
        ]);
    }
}
