<?php

namespace App\Http\Controllers\Admin;

use App\Models\Locker;
use App\Models\LockerStatusLog;
use App\Models\Location;
use App\Services\LockerStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LockerMonitorController extends Controller
{
    public function index(Request $request, LockerStatusService $service): View
    {
        $this->authorizePermission('view lockers');

        $user = $request->user();

        $lockers = $service->getFilteredQuery(
            user:       $user,
            locationId: $request->integer('location_id') ?: null,
            status:     $request->input('status') ?: null,
            companyId:  $request->integer('company_id') ?: null,
        )
            ->get();

        $logs = LockerStatusLog::query()
            ->with(['locker', 'changedBy'])
            ->latestFirst()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->forCompany($user->company_id))
            ->limit(30)
            ->get();

        $locations = Location::query()
            ->orderBy('name')
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('company_id', $user->company_id))
            ->get(['id', 'name', 'company_id']);

        if ($request->wantsJson() || $request->input('_fmt') === 'json') {
            return response()->json($lockers->map(fn ($l) => [
                'id'                => $l->id,
                'name'              => $l->name,
                'status'            => $l->status,
                'connection_status' => $l->connection_status ?? '',
                'company'           => $l->company?->name ?? '—',
                'location'          => $l->location?->name ?? '—',
                'last_seen'         => $l->last_seen_at?->diffForHumans() ?? '—',
            ]));
        }

        return view('admin.lockers.index', [
            'lockers'    => $lockers,
            'logs'       => $logs,
            'locations'  => $locations,
            'companies'  => $this->companiesForFilter(),
            'counts'     => [
                'available' => $lockers->where('status', Locker::STATUS_AVAILABLE)->count(),
                'in_use'    => $lockers->where('status', Locker::STATUS_IN_USE)->count(),
                'fault'     => $lockers->where('status', Locker::STATUS_FAULT)->count(),
                'offline'   => $lockers->where('status', Locker::STATUS_OFFLINE)->count(),
                'disabled'  => $lockers->where('status', Locker::STATUS_DISABLED)->count(),
            ],
            'filters'    => $request->only(['status', 'location_id', 'company_id']),
            'lastSync'   => $this->lastApiSync(),
            'statusOpts' => Locker::statusOptions(),
        ]);
    }

    public function updateStatus(Request $request, Locker $locker, LockerStatusService $service): RedirectResponse
    {
        $this->authorizePermission('edit lockers');

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $service->updateStatus(
            locker:    $locker,
            newStatus: $validated['status'],
            changedBy: $request->user(),
            reason:    $validated['reason'] ?? null,
        );

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'อัปเดตสถานะแล้ว']);
        }

        return back()->with('success', 'อัปเดตสถานะแล้ว');
    }
}
