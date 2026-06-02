<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PermissionAuditLog;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct(private readonly UserManagementService $service) {}

    /**
     * GET /api/role-permissions
     * Returns every role (except super_admin) with its current permission set
     * and a count of users holding that role.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isSuperAdmin()) abort(403);

        $roles = Role::with('permissions')
            ->orderBy('name')
            ->get()
            ->map(function (Role $role) {
                return [
                    'id'          => $role->id,
                    'name'        => $role->name,
                    'permissions' => $role->permissions->pluck('name')->sort()->values(),
                    'user_count'  => $role->users()->count(),
                ];
            });

        $permissions = Permission::orderBy('name')->pluck('name');
        $groups      = PermissionAuditLog::permissionGroups();

        return response()->json([
            'roles'       => $roles,
            'permissions' => $permissions,
            'groups'      => $groups,
        ]);
    }

    /**
     * PATCH /api/role-permissions/{role}
     * Replace permission set for a specific role (super_admin only).
     */
    public function update(Request $request, string $role): JsonResponse
    {
        if (! $request->user()->isSuperAdmin()) abort(403);

        if ($role === 'super_admin') {
            abort(422, 'Super Admin permissions cannot be modified.');
        }

        $request->validate([
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $this->service->updateRolePermissions(
            $role,
            $request->input('permissions', []),
            $request->user(),
        );

        $updated = Role::findByName($role, 'web')->load('permissions');

        return response()->json([
            'role'        => $role,
            'permissions' => $updated->permissions->pluck('name')->sort()->values(),
            'message'     => "Permissions for '{$role}' updated.",
        ]);
    }

    /**
     * GET /api/role-permissions/audit-log
     * History of all role-level permission changes.
     */
    public function auditLog(Request $request): JsonResponse
    {
        if (! $request->user()->isSuperAdmin()) abort(403);

        $logs = PermissionAuditLog::where('target_type', 'role')
            ->with('causer')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json($logs);
    }
}
