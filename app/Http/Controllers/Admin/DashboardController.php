<?php

namespace App\Http\Controllers\Admin;

use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardStatsService $stats): View
    {
        $this->authorizePermission('view lockers');

        $user    = $request->user();
        $filters = array_filter([
            'company_id'  => $request->integer('company_id') ?: null,
            'location_id' => $request->integer('location_id') ?: null,
            'date_from'   => $request->input('date_from', now()->subDays(7)->toDateString()),
            'date_to'     => $request->input('date_to', now()->toDateString()),
        ]);

        return view('admin.dashboard', [
            'filters'    => $filters,
            'companies'  => $this->companiesForFilter(),
            'lockers'    => $stats->getLockerSummary($user, $filters),
            'connection' => $stats->getConnectionSummary($user, $filters),
            'boxes'      => $stats->getBoxSummary($user, $filters),
            'trend'      => $stats->getUsageTrend($user, $filters),
            'lastSync'   => $this->lastApiSync(),
        ]);
    }
}
