<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLockerUserRequest;
use App\Http\Requests\UpdateLockerUserRequest;
use App\Models\LockerUser;
use App\Models\PermissionAuditLog;
use App\Services\LockerUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LockerUserController extends Controller
{
    public function __construct(private readonly LockerUserService $service) {}

    /** GET /api/locker-users */
    public function index(Request $request): JsonResponse
    {
        $query = $this->service->getAccessibleLockerUsers($request->user());

        // Search
        if ($s = $request->string('search')->trim()->toString()) {
            $query->where(fn ($q) =>
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%")
                  ->orWhere('organization', 'like', "%{$s}%")
            );
        }

        // Company filter
        if ($companyId = $request->integer('company_id')) {
            $query->where('company_id', $companyId);
        }

        // User type filter
        if ($typeId = $request->integer('user_type_id')) {
            $query->where('user_type_id', $typeId);
        }

        // Active filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json(
            $query->paginate($request->integer('per_page', 25))
        );
    }

    /** POST /api/locker-users */
    public function store(StoreLockerUserRequest $request): JsonResponse
    {
        $lockerUser = $this->service->createLockerUser($request->validated(), $request->user());

        return response()->json([
            'data'    => $lockerUser->load(['company', 'userType', 'creator']),
            'message' => 'Locker user created successfully.',
        ], 201);
    }

    /** GET /api/locker-users/{lockerUser} */
    public function show(Request $request, LockerUser $lockerUser): JsonResponse
    {
        $this->service->authorizeEditLockerUser($request->user(), $lockerUser);

        return response()->json([
            'data' => $lockerUser->load(['company', 'userType', 'creator', 'updater']),
        ]);
    }

    /** PATCH /api/locker-users/{lockerUser} */
    public function update(UpdateLockerUserRequest $request, LockerUser $lockerUser): JsonResponse
    {
        $this->service->authorizeEditLockerUser($request->user(), $lockerUser);
        $this->service->updateLockerUser($lockerUser, $request->validated(), $request->user());

        return response()->json([
            'data'    => $lockerUser->fresh()->load(['company', 'userType']),
            'message' => 'Locker user updated successfully.',
        ]);
    }

    /** PATCH /api/locker-users/{lockerUser}/disable */
    public function disable(Request $request, LockerUser $lockerUser): JsonResponse
    {
        if (! $request->user()->can('disable locker users')) abort(403);

        $this->service->disableLockerUser($lockerUser, $request->user());

        return response()->json(['message' => "Locker user '{$lockerUser->full_name}' disabled."]);
    }

    /** PATCH /api/locker-users/{lockerUser}/enable */
    public function enable(Request $request, LockerUser $lockerUser): JsonResponse
    {
        if (! $request->user()->can('disable locker users')) abort(403);

        $this->service->enableLockerUser($lockerUser, $request->user());

        return response()->json(['message' => "Locker user '{$lockerUser->full_name}' enabled."]);
    }

    /** GET /api/locker-users/{lockerUser}/audit-log */
    public function auditLog(Request $request, LockerUser $lockerUser): JsonResponse
    {
        $this->service->authorizeEditLockerUser($request->user(), $lockerUser);

        $logs = PermissionAuditLog::where('target_type', 'locker_user')
            ->where('target_id', $lockerUser->id)
            ->with('causer')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json($logs);
    }
}
