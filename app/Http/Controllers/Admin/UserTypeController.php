<?php

namespace App\Http\Controllers\Admin;

use App\Models\UserType;
use App\Services\LockerUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserTypeController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $this->authorizePermission('manage user types');

        $query = UserType::query()->with('company')->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->whereIn('company_id', $request->user()->accessibleCompanyIds());
        }

        if ($request->wantsJson() || $request->input('_fmt') === 'json') {
            return response()->json($query->get()->map(fn ($t) => [
                'id'      => $t->id,
                'name'    => $t->name,
                'company' => $t->company?->name ?? '—',
            ]));
        }

        return view('admin.user-types.index', [
            'types'     => $query->paginate(25),
            'companies' => $this->companiesForFilter(),
            'lastSync'  => $this->lastApiSync(),
        ]);
    }

    public function store(Request $request, LockerUserService $service): RedirectResponse
    {
        $this->authorizePermission('manage user types');

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'code'       => ['nullable', 'string', 'max:50'],
        ]);

        $service->createUserType($validated, $request->user());

        return back()->with('success', 'สร้างประเภทผู้ใช้แล้ว');
    }

    public function update(Request $request, UserType $userType, LockerUserService $service): RedirectResponse
    {
        $this->authorizePermission('manage user types');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $service->updateUserType($userType, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'บันทึกแล้ว']);
        }

        return back()->with('success', 'บันทึกแล้ว');
    }
}
