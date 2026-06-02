<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Locker;
use App\Services\ConnectionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives heartbeat pings from physical locker devices.
 *
 * Route: POST /api/heartbeat
 * Auth:  Authorization: Bearer <api_token>   (locker token, NOT Sanctum)
 *
 * Expected body (all optional except covered by token auth):
 * {
 *   "firmware": "1.0.5",
 *   "ip_address": "10.10.70.213",
 *   "status": "available",        // optional operational status update
 *   "boxes": [                     // optional per-box status snapshot
 *     { "box_number": 1, "status": "available" },
 *     ...
 *   ]
 * }
 */
class HeartbeatController extends Controller
{
    public function __construct(
        private readonly ConnectionStatusService $connectionService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var Locker $locker */
        $locker = $request->attributes->get('authenticated_locker');

        $validated = $request->validate([
            'firmware'   => ['nullable', 'string', 'max:50'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'status'     => ['nullable', 'string', 'in:available,in_use,fault,offline,disabled'],
            'boxes'      => ['nullable', 'array'],
            'boxes.*.box_number' => ['required_with:boxes', 'integer', 'min:1'],
            'boxes.*.status'     => ['required_with:boxes', 'string'],
        ]);

        // Optional: update operational status if device reports it
        if (! empty($validated['status'])) {
            $locker->update(['status' => $validated['status']]);
        }

        $statusChanged = $this->connectionService->processHeartbeat(
            locker:          $locker,
            firmwareVersion: $validated['firmware']   ?? null,
            ipAddress:       $validated['ip_address'] ?? null,
        );

        return response()->json([
            'ok'             => true,
            'locker_id'      => $locker->id,
            'connection_status' => $locker->fresh()->connection_status,
            'last_seen_at'   => $locker->fresh()->last_seen_at,
            'status_changed' => $statusChanged,
        ]);
    }
}
