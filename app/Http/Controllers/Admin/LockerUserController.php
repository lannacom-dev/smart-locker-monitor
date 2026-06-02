<?php

namespace App\Http\Controllers\Admin;

use App\Models\LockerUser;
use App\Services\LockerUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LockerUserController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $this->authorizePermission('view locker users');

        $query = LockerUser::query()->with(['company', 'userType'])->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->whereIn('company_id', $request->user()->accessibleCompanyIds());
        } elseif ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->wantsJson() || $request->input('_fmt') === 'json') {
            return response()->json($query->get()->map(fn ($u) => $this->toRow($u)));
        }

        return view('admin.locker-users.index', [
            'users'     => $query->paginate(25)->withQueryString(),
            'companies' => $this->companiesForFilter(),
            'filters'   => $request->only('company_id'),
            'lastSync'  => $this->lastApiSync(),
        ]);
    }

    private function toRow(LockerUser $u): array
    {
        return [
            'id'      => $u->id,
            'name'    => $u->name,
            'email'   => $u->email ?? '',
            'phone'   => $u->phone ?? '',
            'company' => $u->company?->name ?? '—',
        ];
    }

    public function show(LockerUser $lockerUser): View
    {
        $this->authorizePermission('view locker users');
        abort_unless(auth()->user()->canAccessCompany($lockerUser->company_id), 403);

        $lockerUser->load(['company', 'userType']);

        return view('admin.locker-users.show', compact('lockerUser'));
    }

    public function update(Request $request, LockerUser $lockerUser, LockerUserService $service): RedirectResponse
    {
        $this->authorizePermission('edit locker users');

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['nullable', 'email'],
            'phone'     => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $service->updateLockerUser($lockerUser, $validated, $request->user());

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'บันทึกแล้ว']);
        }

        return back()->with('success', 'บันทึกแล้ว');
    }
}
