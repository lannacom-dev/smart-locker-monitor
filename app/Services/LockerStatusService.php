<?php

namespace App\Services;

use App\Events\LockerStatusUpdated;
use App\Models\Locker;
use App\Models\LockerStatusLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LockerStatusService
{
    /**
     * Update a locker's operational status, write an audit log entry, and broadcast the change.
     *
     * @throws AuthorizationException
     * @throws \InvalidArgumentException
     */
    public function updateStatus(
        Locker  $locker,
        string  $newStatus,
        User    $changedBy,
        ?string $reason = null
    ): LockerStatusLog {
        $this->authorizeUpdate($changedBy, $locker);
        $this->validateStatus($newStatus);

        $log = DB::transaction(function () use ($locker, $newStatus, $changedBy, $reason) {
            $oldStatus = $locker->status;

            $locker->update(['status' => $newStatus]);

            return LockerStatusLog::create([
                'company_id' => $locker->company_id,
                'locker_id'  => $locker->id,
                'changed_by' => $changedBy->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason'     => $reason,
            ]);
        });

        LockerStatusUpdated::dispatch(
            lockerId:    $locker->id,
            lockerName:  $locker->name,
            companyId:   $locker->company_id,
            oldStatus:   $log->old_status,
            newStatus:   $log->new_status,
            changedBy:   $changedBy->id,
            changedAt:   $log->created_at->toIso8601String(),
        );

        return $log;
    }

    /**
     * Return a query builder for lockers scoped to the user's access level with optional filters.
     * Super admin may pass $companyId to filter; tenant admin is always scoped to their company.
     */
    public function getFilteredQuery(
        User   $user,
        ?int   $locationId = null,
        ?string $status     = null,
        ?int   $companyId  = null
    ): Builder {
        $query = Locker::with(['company', 'location'])
            ->withCount('boxes')
            ->orderBy('name');

        if ($user->isSuperAdmin()) {
            if ($companyId !== null) {
                $query->forCompany($companyId);
            }
        } else {
            // Scope to the full reseller subtree (own company + all descendants)
            $ids = $user->accessibleCompanyIds();
            count($ids) === 1
                ? $query->forCompany($ids[0])
                : $query->whereIn('company_id', $ids);
        }

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query;
    }

    private function validateStatus(string $status): void
    {
        if (!array_key_exists($status, Locker::statusOptions())) {
            throw new \InvalidArgumentException("Invalid locker status: {$status}");
        }
    }

    private function authorizeUpdate(User $user, Locker $locker): void
    {
        if (!$user->isSuperAdmin() && !$user->isTenantAdmin()) {
            throw new AuthorizationException('Only Super Admin or Tenant Admin can update locker status.');
        }

        if (!$user->canAccessCompany($locker->company_id)) {
            throw new AuthorizationException('You do not have access to this company\'s lockers.');
        }
    }
}
