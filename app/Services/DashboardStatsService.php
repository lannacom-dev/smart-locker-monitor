<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Locker;
use App\Models\LockerBox;
use App\Models\LockerConnection;
use App\Models\LockerEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates all statistics for the usage dashboard.
 *
 * Every method accepts a $filters array and a $user for tenant scoping.
 *
 * Supported filters:
 *   company_id   int|null   — super_admin only
 *   location_id  int|null
 *   date_from    string ('Y-m-d')
 *   date_to      string ('Y-m-d')
 */
class DashboardStatsService
{
    // ──────────────────────────────────────────────────────────────
    // Locker counts
    // ──────────────────────────────────────────────────────────────

    public function getLockerSummary(User $user, array $filters = []): array
    {
        // Single GROUP BY — derive total from the row counts, no second COUNT query
        $rows = $this->baseLockerQuery($user, $filters)
            ->select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return [
            'total'    => $rows->sum(),
            'available'=> (int) ($rows['available'] ?? 0),
            'in_use'   => (int) ($rows['in_use']    ?? 0),
            'fault'    => (int) ($rows['fault']      ?? 0),
            'offline'  => (int) ($rows['offline']    ?? 0),
            'disabled' => (int) ($rows['disabled']   ?? 0),
        ];
    }

    public function getConnectionSummary(User $user, array $filters = []): array
    {
        $query = $this->baseLockerQuery($user, $filters);

        $rows = (clone $query)
            ->select('connection_status', DB::raw('count(*) as cnt'))
            ->groupBy('connection_status')
            ->pluck('cnt', 'connection_status');

        return [
            'online'  => (int) ($rows[LockerConnection::STATUS_ONLINE]  ?? 0),
            'warning' => (int) ($rows[LockerConnection::STATUS_WARNING] ?? 0),
            'offline' => (int) ($rows[LockerConnection::STATUS_OFFLINE] ?? 0),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Box counts
    // ──────────────────────────────────────────────────────────────

    public function getBoxSummary(User $user, array $filters = []): array
    {
        // Single GROUP BY — derive total from row counts, no second COUNT query
        $rows = $this->baseBoxQuery($user, $filters)
            ->select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return [
            'total'    => $rows->sum(),
            'available'=> (int) ($rows['available'] ?? 0),
            'occupied' => (int) ($rows['occupied']  ?? 0),
            'open'     => (int) ($rows['open']       ?? 0),
            'error'    => (int) ($rows['error']      ?? 0),
            'disabled' => (int) ($rows['disabled']   ?? 0),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // User count
    // ──────────────────────────────────────────────────────────────

    public function getUserCount(User $user, array $filters = []): int
    {
        $query = User::query()->where('is_active', true);

        if ($user->isSuperAdmin()) {
            if (! empty($filters['company_id'])) {
                $query->where('company_id', $filters['company_id']);
            }
        } else {
            $ids = $user->accessibleCompanyIds();
            count($ids) === 1
                ? $query->where('company_id', $ids[0])
                : $query->whereIn('company_id', $ids);
        }

        return $query->count();
    }

    // ──────────────────────────────────────────────────────────────
    // Transaction counts  (usage = "open" events on locker boxes)
    // ──────────────────────────────────────────────────────────────

    public function getTransactionCounts(User $user, array $filters = []): array
    {
        // Single round-trip: CASE/SUM replaces 7 separate COUNT clones
        $now           = now();
        $todayDate     = today()->format('Y-m-d');
        $yesterdayDate = today()->subDay()->format('Y-m-d');
        $weekStart     = $now->copy()->startOfWeek();
        $monthStart    = $now->copy()->startOfMonth();
        $lastWeekStart = $now->copy()->subWeek()->startOfWeek();
        $lastWeekEnd   = $now->copy()->subWeek()->endOfWeek();

        $row = $this->baseEventQuery($user, $filters)
            ->whereIn('event_type', [LockerEvent::TYPE_OPEN, LockerEvent::TYPE_UNLOCK])
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN CAST(locker_events.created_at AS DATE) = ? THEN 1 ELSE 0 END) AS today_cnt,
                SUM(CASE WHEN CAST(locker_events.created_at AS DATE) = ? THEN 1 ELSE 0 END) AS yesterday_cnt,
                SUM(CASE WHEN locker_events.created_at >= ? THEN 1 ELSE 0 END) AS this_week_cnt,
                SUM(CASE WHEN locker_events.created_at >= ? THEN 1 ELSE 0 END) AS this_month_cnt,
                SUM(CASE WHEN locker_events.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS last_week_cnt
            ", [$todayDate, $yesterdayDate, $weekStart, $monthStart, $lastWeekStart, $lastWeekEnd])
            ->first();

        $today     = (int) $row->today_cnt;
        $yesterday = (int) $row->yesterday_cnt;
        $thisWeek  = (int) $row->this_week_cnt;
        $thisMonth = (int) $row->this_month_cnt;
        $total     = (int) $row->total;
        $lastWeek  = (int) $row->last_week_cnt;

        $todayChangePct = $yesterday > 0 ? round((($today - $yesterday) / $yesterday) * 100, 1) : null;
        $weekChangePct  = $lastWeek  > 0 ? round((($thisWeek - $lastWeek) / $lastWeek)   * 100, 1) : null;

        return compact('today', 'thisWeek', 'thisMonth', 'total', 'todayChangePct', 'weekChangePct');
    }

    // ──────────────────────────────────────────────────────────────
    // Usage trend (daily counts over N days — for line/bar chart)
    // ──────────────────────────────────────────────────────────────

    public function getUsageTrend(User $user, array $filters = [], int $days = 30): array
    {
        $from = Carbon::parse($filters['date_from'] ?? now()->subDays($days - 1)->startOfDay());
        $to   = Carbon::parse($filters['date_to']   ?? now()->endOfDay());

        // Clamp to $days maximum when no explicit range
        if (empty($filters['date_from']) && $from->diffInDays($to) > 90) {
            $from = $to->copy()->subDays($days - 1)->startOfDay();
        }

        $rows = $this->baseEventQuery($user, $filters)
            ->whereIn('event_type', [LockerEvent::TYPE_OPEN, LockerEvent::TYPE_UNLOCK])
            ->where('locker_events.created_at', '>=', $from)
            ->where('locker_events.created_at', '<=', $to)
            ->select(
                DB::raw("CAST(locker_events.created_at AS DATE) as event_date"),
                DB::raw('count(*) as cnt')
            )
            ->groupBy(DB::raw("CAST(locker_events.created_at AS DATE)"))
            ->orderBy('event_date')
            ->pluck('cnt', 'event_date')
            ->map(fn($v) => (int) $v);

        // Fill in zeroes for days with no events
        $labels = [];
        $data   = [];
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to->copy()->startOfDay())) {
            $key      = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d M');
            $data[]   = (int) ($rows[$key] ?? 0);
            $cursor->addDay();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Event counts broken down by event_type (for doughnut chart)
     */
    public function getEventTypeBreakdown(User $user, array $filters = []): array
    {
        $rows = $this->baseEventQuery($user, $filters)
            ->select('event_type', DB::raw('count(*) as cnt'))
            ->groupBy('event_type')
            ->pluck('cnt', 'event_type');

        return [
            'labels' => array_map('ucfirst', $rows->keys()->toArray()),
            'data'   => $rows->values()->map(fn($v) => (int) $v)->toArray(),
        ];
    }

    /**
     * Top N most-used lockers (by open events)
     */
    public function getTopLockers(User $user, array $filters = [], int $limit = 7): array
    {
        $rows = $this->baseEventQuery($user, $filters)
            ->whereIn('locker_events.event_type', [LockerEvent::TYPE_OPEN, LockerEvent::TYPE_UNLOCK])
            ->select('lockers.name', DB::raw('count(locker_events.id) as cnt'))
            ->join('lockers', 'lockers.id', '=', 'locker_events.locker_id')
            ->groupBy('lockers.name')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->pluck('cnt', 'lockers.name');

        return [
            'labels' => $rows->keys()->toArray(),
            'data'   => $rows->values()->map(fn($v) => (int) $v)->toArray(),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // All stats in one call (used by API endpoint)
    // ──────────────────────────────────────────────────────────────

    public function getAllStats(User $user, array $filters = []): array
    {
        return [
            'lockers'      => $this->getLockerSummary($user, $filters),
            'connection'   => $this->getConnectionSummary($user, $filters),
            'boxes'        => $this->getBoxSummary($user, $filters),
            'users'        => $this->getUserCount($user, $filters),
            'transactions' => $this->getTransactionCounts($user, $filters),
            'trend'        => $this->getUsageTrend($user, $filters),
            'event_types'  => $this->getEventTypeBreakdown($user, $filters),
            'top_lockers'  => $this->getTopLockers($user, $filters),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Base query builders
    // ──────────────────────────────────────────────────────────────

    private function baseLockerQuery(User $user, array $filters)
    {
        $q = Locker::query()->where('is_active', true);

        $this->applyCompanyScope($q, $user, $filters, 'lockers');

        if (! empty($filters['location_id'])) {
            $q->where('location_id', $filters['location_id']);
        }

        return $q;
    }

    private function baseBoxQuery(User $user, array $filters)
    {
        $q = LockerBox::query()->where('is_active', true);

        $this->applyCompanyScope($q, $user, $filters, 'locker_boxes');

        if (! empty($filters['location_id'])) {
            $q->whereHas('locker', fn($lq) => $lq->where('location_id', $filters['location_id']));
        }

        return $q;
    }

    private function baseEventQuery(User $user, array $filters)
    {
        $q = LockerEvent::query();

        $this->applyCompanyScope($q, $user, $filters, 'locker_events');

        if (! empty($filters['location_id'])) {
            $q->whereHas('locker', fn($lq) => $lq->where('location_id', $filters['location_id']));
        }

        if (! empty($filters['date_from'])) {
            $q->where('locker_events.created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $q->where('locker_events.created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $q;
    }

    private function applyCompanyScope($query, User $user, array $filters, string $table): void
    {
        if ($user->isSuperAdmin()) {
            // Super admin: optionally filter by a specific company_id from the request
            if (! empty($filters['company_id'])) {
                $query->where("{$table}.company_id", $filters['company_id']);
            }
            return;
        }

        // Non-super-admin: scope to the full reseller subtree (own company + descendants)
        $ids = $user->accessibleCompanyIds();

        if (count($ids) === 1) {
            $query->where("{$table}.company_id", $ids[0]);
        } else {
            $query->whereIn("{$table}.company_id", $ids);
        }
    }
}
