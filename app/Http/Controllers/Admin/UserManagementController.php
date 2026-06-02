<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $this->authorizePermission('view users');

        $query = User::query()->with(['company', 'roles'])->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->whereIn('company_id', $request->user()->accessibleCompanyIds());
        } elseif ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->wantsJson() || $request->input('_fmt') === 'json') {
            return response()->json($query->get()->map(fn ($u) => [
                'id'      => $u->id,
                'name'    => $u->name,
                'email'   => $u->email,
                'roles'   => $u->roles->pluck('name')->join(', '),
                'company' => $u->company?->name ?? '—',
            ]));
        }

        return view('admin.users.index', [
            'users'     => $query->paginate(25)->withQueryString(),
            'companies' => $this->companiesForFilter(),
            'filters'   => $request->only('company_id'),
            'lastSync'  => $this->lastApiSync(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizePermission('create users');

        $companies = $request->user()->isSuperAdmin()
            ? Company::orderBy('name')->get(['id', 'name'])
            : Company::whereIn('id', $request->user()->accessibleCompanyIds())->orderBy('name')->get(['id', 'name']);

        return view('admin.users.create', [
            'companies' => $companies,
            'roles'     => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('create users');

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'roles'      => ['nullable', 'array'],
            'is_active'  => ['boolean'],
        ]);

        // Tenant admin can only create users in their own companies
        if (! $request->user()->isSuperAdmin() && $validated['company_id']) {
            abort_unless($request->user()->canAccessCompany((int) $validated['company_id']), 403);
        }

        $user = User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'company_id' => $validated['company_id'] ?? null,
            'is_active'  => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('admin.users.index')->with('success', "สร้าง user {$user->email} แล้ว");
    }

    public function show(User $user): View
    {
        $this->authorizePermission('view users');
        abort_unless(auth()->user()->canAccessCompany($user->company_id ?? 0) || auth()->user()->isSuperAdmin(), 403);

        $user->load(['company', 'roles']);

        return view('admin.users.show', [
            'user'  => $user,
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, User $user, UserManagementService $service): RedirectResponse
    {
        $this->authorizePermission('edit users');

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email'],
            'is_active' => ['boolean'],
            'roles'     => ['nullable', 'array'],
        ]);

        $service->updateUser($user, $validated, $request->user());

        if (! empty($validated['roles'])) {
            $service->syncRoles($user, $validated['roles'], $request->user());
        }

        return back()->with('success', 'บันทึกผู้ใช้แล้ว');
    }
}
