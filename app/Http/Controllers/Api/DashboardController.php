<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/dashboard/stats
 *
 * Query params:
 *   company_id   (super_admin only)
 *   location_id
 *   date_from    Y-m-d
 *   date_to      Y-m-d
 *   trend_days   int (default 30, max 90)
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardStatsService $stats) {}

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'company_id'  => ['nullable', 'integer'],
            'location_id' => ['nullable', 'integer'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date', 'after_or_equal:date_from'],
            'trend_days'  => ['nullable', 'integer', 'min:7', 'max:90'],
        ]);

        $user    = $request->user();
        $filters = array_filter([
            'company_id'  => $user->isSuperAdmin() ? $request->integer('company_id') ?: null : null,
            'location_id' => $request->integer('location_id') ?: null,
            'date_from'   => $request->date_from,
            'date_to'     => $request->date_to,
        ]);

        $trendDays = min((int) ($request->trend_days ?: 30), 90);

        return response()->json([
            ...$this->stats->getAllStats($user, $filters),
            'trend' => $this->stats->getUsageTrend($user, $filters, $trendDays),
        ]);
    }
}
