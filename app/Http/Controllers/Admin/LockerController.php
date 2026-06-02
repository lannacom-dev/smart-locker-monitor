<?php

namespace App\Http\Controllers\Admin;

use App\Models\Locker;
use App\Services\LockerEditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LockerController extends Controller
{
    public function show(Locker $locker): View
    {
        $this->authorizePermission('view lockers');
        abort_unless(auth()->user()->canAccessCompany($locker->company_id), 403);

        $locker->load(['company', 'location', 'boxes', 'statusLogs' => fn ($q) => $q->latestFirst()->limit(20)->with('changedBy')]);

        return view('admin.lockers.show', compact('locker'));
    }

    public function edit(Locker $locker): View
    {
        $this->authorizePermission('edit lockers');
        abort_unless(auth()->user()->canAccessCompany($locker->company_id), 403);

        $locker->load(['company', 'location']);

        return view('admin.lockers.edit', [
            'locker' => $locker,
        ]);
    }

    public function update(Request $request, Locker $locker, LockerEditService $service): RedirectResponse
    {
        $this->authorizePermission('edit lockers');

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'external_unit_id' => ['nullable', 'integer'],
            'is_active'        => ['boolean'],
        ]);

        $service->updateFields($locker, $request->user(), $validated);

        return redirect()->route('admin.lockers.show', $locker)->with('success', 'บันทึกแล้ว');
    }
}
