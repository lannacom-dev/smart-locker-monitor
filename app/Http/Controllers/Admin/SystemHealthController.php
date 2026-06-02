<?php

namespace App\Http\Controllers\Admin;

use App\Models\SystemAlert;
use App\Services\SystemHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(Request $request, SystemHealthService $health): View
    {
        $this->authorizePermission('view system health');

        $companyId = $request->integer('company_id') ?: null;
        $user      = $request->user();

        return view('admin.health.index', [
            'overall'     => $health->getOverallStatus($user, $companyId),
            'checks'      => $health->getLatestChecks($user, $companyId),
            'summary'     => $health->getIssueSummary($user, $companyId),
            'alerts'      => $health->getAlertsQuery(
                user:            $user,
                filterCompanyId: $companyId,
                filterStatus:    $request->input('alert_status', 'open') ?: null,
                filterSeverity:  $request->input('alert_severity') ?: null,
            )->limit(50)->get(),
            'companies'   => $this->companiesForFilter(),
            'filters'     => $request->only(['company_id', 'alert_status', 'alert_severity']),
            'lastSync'    => $this->lastApiSync(),
        ]);
    }

    public function acknowledge(Request $request, SystemAlert $alert, SystemHealthService $health): RedirectResponse
    {
        $this->authorizePermission('acknowledge alerts');

        $validated = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $health->acknowledge($alert, $request->user(), $validated['note'] ?? null);

        return back()->with('success', 'รับทราบ Alert แล้ว');
    }
}
