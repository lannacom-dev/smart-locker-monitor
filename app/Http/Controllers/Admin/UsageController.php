<?php

namespace App\Http\Controllers\Admin;

use App\Models\Location;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsageController extends Controller
{
    public function index(Request $request, DashboardStatsService $stats): View
    {
        $this->authorizePermission('view lockers');

        $user    = $request->user();
        $filters = array_filter([
            'company_id'  => $request->integer('company_id') ?: null,
            'location_id' => $request->integer('location_id') ?: null,
            'date_from'   => $request->input('date_from', now()->subDays(30)->toDateString()),
            'date_to'     => $request->input('date_to', now()->toDateString()),
        ]);

        $locations = Location::query()
            ->orderBy('name')
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('company_id', $user->accessibleCompanyIds()))
            ->get(['id', 'name']);

        return view('admin.usage.index', [
            'filters'   => $filters,
            'companies' => $this->companiesForFilter(),
            'locations' => $locations,
            'lockers'   => $stats->getLockerSummary($user, $filters),
            'boxes'     => $stats->getBoxSummary($user, $filters),
            'trend'     => $stats->getUsageTrend($user, $filters),
            'top'       => $stats->getTopLockers($user, $filters, 10),
            'lastSync'  => $this->lastApiSync(),
        ]);
    }
}
