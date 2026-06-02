<?php

namespace App\Http\Controllers\Admin;

use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $roles = Role::orderBy('name')->get();
        $perms = Permission::orderBy('name')->get();

        $matrix = [];
        foreach ($roles as $role) {
            $matrix[$role->name] = $role->permissions->pluck('name')->flip()->all();
        }

        return view('admin.roles.index', compact('roles', 'perms', 'matrix'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name', 'regex:/^[a-z0-9_]+$/'],
        ], [
            'name.regex' => 'ชื่อ role ใช้ได้เฉพาะ a-z, 0-9 และ _ เท่านั้น',
        ]);

        Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        return back()->with('success', "สร้าง role '{$validated['name']}' แล้ว");
    }

    public function destroy(string $role): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $protected = ['super_admin', 'tenant_admin', 'viewer'];
        abort_if(in_array($role, $protected), 403, "ไม่สามารถลบ role '{$role}' ได้");

        $r = Role::where('name', $role)->firstOrFail();
        $r->delete();

        return back()->with('success', "ลบ role '{$role}' แล้ว");
    }

    public function update(Request $request, string $role, UserManagementService $service): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $service->updateRolePermissions($role, $validated['permissions'] ?? [], $request->user());

        return back()->with('success', 'อัปเดตสิทธิ์ role แล้ว');
    }
}
