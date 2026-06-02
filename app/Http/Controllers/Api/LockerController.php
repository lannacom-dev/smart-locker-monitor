<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLockerRequest;
use App\Models\Locker;
use App\Services\LockerEditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LockerController extends Controller
{
    public function __construct(private readonly LockerEditService $service) {}

    /**
     * GET /api/lockers/{locker}
     * Show full locker detail including edit history.
     */
    public function show(Request $request, Locker $locker): JsonResponse
    {
        $this->service->authorizeView($request->user(), $locker);

        $locker->load([
            'company',
            'location',
            'tenant',
            'editLogs' => fn($q) => $q->with('changedBy')->orderByDesc('created_at')->limit(50),
        ]);

        $canEditSensitive = $request->user()->isSuperAdmin();

        return response()->json([
            'data'              => $locker,
            'can_edit'          => $request->user()->can('edit lockers'),
            'can_edit_sensitive'=> $canEditSensitive,
        ]);
    }

    /**
     * PATCH /api/lockers/{locker}
     * Update allowed fields. Sensitive fields blocked for non-super_admin.
     */
    public function update(UpdateLockerRequest $request, Locker $locker): JsonResponse
    {
        $fields = $request->lockerFields();
        $note   = $request->string('note')->toString() ?: null;

        $this->service->updateFields($locker, $request->user(), $fields, $note);

        return response()->json([
            'data'    => $locker->fresh(['company', 'location', 'tenant']),
            'message' => 'Locker updated successfully.',
        ]);
    }

    /**
     * GET /api/lockers/{locker}/edit-logs
     * Retrieve audit trail for a locker.
     */
    public function editLogs(Request $request, Locker $locker): JsonResponse
    {
        $this->service->authorizeView($request->user(), $locker);

        $logs = $this->service->getEditLogs($locker, $request->integer('limit', 50));

        return response()->json(['data' => $logs]);
    }
}
