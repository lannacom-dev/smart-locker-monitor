<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\PermissionAuditLog;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private readonly UserManagementService $service) {}

    /** GET /api/admin-users */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->can('view users')) abort(403);

        $query = $this->service->getAccessibleUsers($request->user());

        if ($s = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                                       ->orWhere('email', 'like', "%{$s}%"));
        }

        if ($role = $request->string('role')->toString()) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    /** POST /api/admin-users */
    public function store(CreateAdminUserRequest $request): JsonResponse
    {
        $user = $this->service->createUser($request->validated(), $request->user());

        return response()->json([
            'data'    => $user->load('roles', 'company'),
            'message' => 'User created successfully.',
        ], 201);
    }

    /** GET /api/admin-users/{user} */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->service->authorizeView($request->user(), $user);

        return response()->json([
            'data'               => $user->load('roles', 'company'),
            'assignable_roles'   => $this->service->getAssignableRoles($request->user()),
            'can_edit'           => $request->user()->can('edit users'),
            'can_disable'        => $request->user()->can('edit users') && $user->id !== $request->user()->id,
        ]);
    }

    /** PATCH /api/admin-users/{user} */
    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        $this->service->updateUser($user, $request->validated(), $request->user());

        return response()->json([
            'data'    => $user->fresh(['roles', 'company']),
            'message' => 'User updated.',
        ]);
    }

    /** PATCH /api/admin-users/{user}/roles */
    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'note'  => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->syncRoles(
            target:    $user,
            roleNames: $request->input('roles', []),
            actor:     $request->user(),
            note:      $request->string('note')->toString() ?: null,
        );

        return response()->json([
            'data'    => $user->fresh(['roles', 'company']),
            'message' => 'Roles updated.',
        ]);
    }

    /** PATCH /api/admin-users/{user}/disable */
    public function disable(Request $request, User $user): JsonResponse
    {
        $this->service->disableUser($user, $request->user());

        return response()->json(['message' => 'User disabled.']);
    }

    /** PATCH /api/admin-users/{user}/enable */
    public function enable(Request $request, User $user): JsonResponse
    {
        $this->service->enableUser($user, $request->user());

        return response()->json(['message' => 'User enabled.']);
    }

    /** POST /api/admin-users/{user}/reset-password */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $plain = $this->service->resetPassword($user, $request->user());

        return response()->json([
            'message'          => 'Password reset. Share the temporary password securely.',
            'temporary_password' => $plain,
        ]);
    }

    /** GET /api/admin-users/{user}/audit-log */
    public function auditLog(Request $request, User $user): JsonResponse
    {
        $this->service->authorizeView($request->user(), $user);

        $logs = PermissionAuditLog::where('target_type', 'user')
            ->where('target_id', $user->id)
            ->with('causer')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json($logs);
    }
}
