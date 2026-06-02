<?php

namespace App\Http\Controllers\Admin;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $this->authorizePermission('view locations');

        $query = Location::query()->with('company')->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->whereIn('company_id', $request->user()->accessibleCompanyIds());
        }

        if ($request->wantsJson() || $request->input('_fmt') === 'json') {
            return response()->json($query->get()->map(fn ($l) => $this->toRow($l)));
        }

        return view('admin.locations.index', [
            'locations' => $query->paginate(25),
            'lastSync'  => $this->lastApiSync(),
        ]);
    }

    private function toRow(Location $loc): array
    {
        return [
            'id'      => $loc->id,
            'name'    => $loc->name,
            'address' => $loc->address ?? '',
            'company' => $loc->company?->name ?? '—',
        ];
    }

    public function create(): View
    {
        $this->authorizePermission('create locations');

        return view('admin.locations.form', [
            'location'  => new Location(),
            'companies' => $this->companiesForFilter(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('create locations');

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name'       => ['required', 'string', 'max:255'],
            'address'    => ['nullable', 'string'],
            'is_active'  => ['boolean'],
        ]);

        abort_unless($request->user()->canAccessCompany($validated['company_id']), 403);

        Location::create($validated);

        return redirect()->route('admin.locations.index')->with('success', 'สร้างสาขาแล้ว');
    }

    public function edit(Location $location): View
    {
        $this->authorizePermission('edit locations');
        abort_unless(auth()->user()->canAccessCompany($location->company_id), 403);

        return view('admin.locations.form', [
            'location'  => $location,
            'companies' => $this->companiesForFilter(),
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $this->authorizePermission('edit locations');

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $location->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'บันทึกแล้ว']);
        }

        return redirect()->route('admin.locations.index')->with('success', 'บันทึกแล้ว');
    }
}
