<?php

namespace App\Http\Controllers\Admin;

use App\Models\FloorPlan;
use App\Models\LockerLocation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FloorPlanController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission('view lockers');

        $query = FloorPlan::query()->with(['location', 'company'])->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->whereIn('company_id', $request->user()->accessibleCompanyIds());
        }

        return view('admin.floor-plans.index', [
            'plans'    => $query->paginate(25),
            'lastSync' => $this->lastApiSync(),
        ]);
    }

    public function show(FloorPlan $floorPlan): View
    {
        $this->authorizePermission('view lockers');
        abort_unless(auth()->user()->canAccessCompany($floorPlan->company_id), 403);

        $placements = LockerLocation::query()
            ->where('floor_plan_id', $floorPlan->id)
            ->with('locker')
            ->get();

        return view('admin.floor-plans.show', compact('floorPlan', 'placements'));
    }
}
