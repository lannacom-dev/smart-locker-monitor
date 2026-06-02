<?php

namespace App\Services;

use App\Models\CorrectiveMaintenance;
use App\Models\CorrectiveMaintenanceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Business logic for Corrective Maintenance records.
 *
 * State machine:
 *   created     → in_progress, cancelled
 *   in_progress → completed, cancelled
 *   completed   → in_progress   (reopen — fix didn't work)
 *   cancelled   → created       (reactivate — oops)
 */
class CorrectiveMaintenanceService
{
    // ── State machine ─────────────────────────────────────────────

    private const TRANSITIONS = [
        'created'     => ['in_progress', 'cancelled'],
        'in_progress' => ['completed',   'cancelled'],
        'completed'   => ['in_progress'],
        'cancelled'   => ['created'],
    ];

    // ── Create ────────────────────────────────────────────────────

    public function create(User $creator, array $data): CorrectiveMaintenance
    {
        return DB::transaction(function () use ($creator, $data) {
            $maintenance = CorrectiveMaintenance::create([
                'company_id'     => $creator->company_id,
                'locker_id'      => $data['locker_id'],
                'issue_id'       => $data['issue_id']      ?? null,
                'created_by'     => $creator->id,
                'technician_id'  => $data['technician_id'] ?? null,
                'title'          => $data['title'],
                'description'    => $data['description'],
                'root_cause'     => $data['root_cause']    ?? null,
                'priority'       => $data['priority']      ?? CorrectiveMaintenance::PRIORITY_MEDIUM,
                'type'           => $data['type']          ?? CorrectiveMaintenance::TYPE_CORRECTIVE,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'cost_estimate'  => $data['cost_estimate'] ?? null,
                'notes'          => $data['notes']         ?? null,
                'status'         => CorrectiveMaintenance::STATUS_CREATED,
            ]);

            $this->log($maintenance, $creator, CorrectiveMaintenanceLog::ACTION_CREATED,
                null, null, $maintenance->title, 'Maintenance record created');

            Log::info('[Maintenance] Created', [
                'id'         => $maintenance->id,
                'created_by' => $creator->id,
                'locker_id'  => $maintenance->locker_id,
            ]);

            return $maintenance;
        });
    }

    // ── Field Updates (with per-field audit) ──────────────────────

    /**
     * Update any tracked fields. Logs a separate entry for each changed field.
     * Tracked: title, description, root_cause, solution, notes,
     *          priority, scheduled_date, cost_estimate, cost_actual
     */
    public function updateFields(
        CorrectiveMaintenance $maintenance,
        User                  $actor,
        array                 $data,
        ?string               $note = null,
    ): CorrectiveMaintenance {

        $this->authorizeAccess($actor, $maintenance);

        $trackable = [
            'title', 'description', 'root_cause', 'solution', 'notes',
            'priority', 'scheduled_date',
        ];
        $costFields = ['cost_estimate', 'cost_actual'];

        return DB::transaction(function () use ($maintenance, $actor, $data, $note, $trackable, $costFields) {
            foreach ($trackable as $field) {
                if (!array_key_exists($field, $data)) continue;
                $old = $maintenance->$field;
                $new = $data[$field];
                if ((string) $old === (string) $new) continue;

                $maintenance->$field = $new;

                $action = match ($field) {
                    'root_cause' => CorrectiveMaintenanceLog::ACTION_ROOT_CAUSE_UPDATED,
                    'solution'   => CorrectiveMaintenanceLog::ACTION_SOLUTION_UPDATED,
                    default      => CorrectiveMaintenanceLog::ACTION_FIELD_UPDATED,
                };

                $this->log($maintenance, $actor, $action, $field,
                    $old !== null ? (string) $old : null,
                    $new !== null ? (string) $new : null,
                    $note);
            }

            foreach ($costFields as $field) {
                if (!array_key_exists($field, $data)) continue;
                $old = $maintenance->$field;
                $new = $data[$field];
                if ((string) $old === (string) $new) continue;

                $maintenance->$field = $new;

                $this->log($maintenance, $actor, CorrectiveMaintenanceLog::ACTION_COST_UPDATED,
                    $field,
                    $old !== null ? number_format((float) $old, 2) : null,
                    $new !== null ? number_format((float) $new, 2) : null,
                    $note);
            }

            $maintenance->save();
            return $maintenance->refresh();
        });
    }

    /** Convenience: update only root cause. */
    public function updateRootCause(CorrectiveMaintenance $m, User $actor, string $rootCause, ?string $note = null): CorrectiveMaintenance
    {
        return $this->updateFields($m, $actor, ['root_cause' => $rootCause], $note);
    }

    /** Convenience: update only solution. */
    public function updateSolution(CorrectiveMaintenance $m, User $actor, string $solution, ?string $note = null): CorrectiveMaintenance
    {
        return $this->updateFields($m, $actor, ['solution' => $solution], $note);
    }

    // ── Technician Assignment ─────────────────────────────────────

    public function assignTechnician(
        CorrectiveMaintenance $m,
        User                  $actor,
        ?int                  $technicianId,
        ?string               $note = null,
    ): CorrectiveMaintenance {

        $this->authorizeAccess($actor, $m);

        return DB::transaction(function () use ($m, $actor, $technicianId, $note) {
            $oldTech = $m->technician?->name;
            $newTech = $technicianId ? User::find($technicianId)?->name : null;

            $m->update(['technician_id' => $technicianId]);

            $this->log($m, $actor,
                CorrectiveMaintenanceLog::ACTION_TECHNICIAN_ASSIGNED,
                'technician_id', $oldTech, $newTech, $note);

            Log::info('[Maintenance] Technician assigned', [
                'id'           => $m->id,
                'technician'   => $technicianId,
                'by'           => $actor->id,
            ]);

            return $m->refresh();
        });
    }

    // ── Status Transitions ────────────────────────────────────────

    public function getAllowedTransitions(CorrectiveMaintenance $m, User $actor): array
    {
        $from = $m->status;
        $all  = self::TRANSITIONS[$from] ?? [];

        $isAdmin = $this->isAdmin($actor);
        $isTech  = (int) $m->technician_id === $actor->id;
        $canEdit = $actor->can('edit maintenance') && ($isAdmin || $isTech);

        if (!$canEdit) return [];

        // Cancelling requires admin or 'cancel maintenance' permission
        return array_values(array_filter($all, fn($to) =>
            $to === CorrectiveMaintenance::STATUS_CANCELLED
                ? ($isAdmin || $actor->can('cancel maintenance'))
                : $canEdit
        ));
    }

    /**
     * Transition the maintenance record to a new status.
     * Handles all side-effects: timestamps, audit, validation.
     */
    public function transition(
        CorrectiveMaintenance $m,
        User                  $actor,
        string                $toStatus,
        ?string               $note = null,
        array                 $extra = [],
    ): CorrectiveMaintenance {

        $this->authorizeAccess($actor, $m);

        $allowed = $this->getAllowedTransitions($m, $actor);
        if (!in_array($toStatus, $allowed, true)) {
            abort(422, "Cannot transition from '{$m->status}' to '{$toStatus}'. " .
                       'Allowed: ' . implode(', ', $allowed ?: ['none']) . '.');
        }

        return DB::transaction(function () use ($m, $actor, $toStatus, $note, $extra) {
            $fromStatus = $m->status;
            $updates    = ['status' => $toStatus];

            // ── Per-transition side effects ───────────────────────
            switch ($toStatus) {
                case CorrectiveMaintenance::STATUS_IN_PROGRESS:
                    if (!$m->started_at) {
                        $updates['started_at'] = now();
                    }
                    $action = $fromStatus === CorrectiveMaintenance::STATUS_COMPLETED
                        ? CorrectiveMaintenanceLog::ACTION_REOPENED
                        : CorrectiveMaintenanceLog::ACTION_STATUS_CHANGED;
                    break;

                case CorrectiveMaintenance::STATUS_COMPLETED:
                    $updates['completed_at'] = $extra['completed_at'] ?? now();
                    if (isset($extra['solution']) && $extra['solution'] !== '') {
                        $updates['solution'] = $extra['solution'];
                    }
                    if (isset($extra['cost_actual'])) {
                        $updates['cost_actual'] = $extra['cost_actual'];
                    }
                    $action = CorrectiveMaintenanceLog::ACTION_COMPLETED;
                    break;

                case CorrectiveMaintenance::STATUS_CANCELLED:
                    $updates['cancelled_at']  = now();
                    $updates['cancel_reason'] = $note ?? $extra['cancel_reason'] ?? null;
                    $action = CorrectiveMaintenanceLog::ACTION_CANCELLED;
                    break;

                case CorrectiveMaintenance::STATUS_CREATED:
                    // Reactivated from cancelled
                    $updates['cancelled_at']  = null;
                    $updates['cancel_reason'] = null;
                    $action = CorrectiveMaintenanceLog::ACTION_REACTIVATED;
                    break;

                default:
                    $action = CorrectiveMaintenanceLog::ACTION_STATUS_CHANGED;
            }

            $m->update($updates);

            $this->log($m, $actor, $action, 'status', $fromStatus, $toStatus, $note);

            // If solution was updated during complete, log it too
            if ($toStatus === CorrectiveMaintenance::STATUS_COMPLETED
                && isset($extra['solution']) && $extra['solution'] !== '') {
                $this->log($m, $actor,
                    CorrectiveMaintenanceLog::ACTION_SOLUTION_UPDATED,
                    'solution', null, $extra['solution'], null);
            }

            Log::info('[Maintenance] Status changed', [
                'id'     => $m->id,
                'from'   => $fromStatus,
                'to'     => $toStatus,
                'by'     => $actor->id,
            ]);

            return $m->refresh();
        });
    }

    // ── Queries ───────────────────────────────────────────────────

    public function getListQuery(User $user, array $filters = []): Builder
    {
        $query = CorrectiveMaintenance::with([
            'locker', 'company', 'technician', 'creator', 'issue',
        ])->orderByDesc('created_at');

        // Tenant scoping
        $ids = $user->accessibleCompanyIds();

        if (!empty($filters['company_id'])) {
            $cid = (int) $filters['company_id'];
            in_array($cid, $ids, true)
                ? $query->where('company_id', $cid)
                : $query->whereRaw('1=0');
        } else {
            count($ids) === 1
                ? $query->where('company_id', $ids[0])
                : $query->whereIn('company_id', $ids);
        }

        if (!empty($filters['status']))    $query->where('status',       $filters['status']);
        if (!empty($filters['priority']))  $query->where('priority',     $filters['priority']);
        if (!empty($filters['locker_id'])) $query->where('locker_id',    $filters['locker_id']);
        if (!empty($filters['issue_id']))  $query->where('issue_id',     $filters['issue_id']);

        if (isset($filters['technician_id'])) {
            $tid = $filters['technician_id'];
            $tid === 'me'
                ? $query->where('technician_id', $user->id)
                : ($tid === 'unassigned'
                    ? $query->whereNull('technician_id')
                    : $query->where('technician_id', (int) $tid));
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(fn($q) =>
                $q->where('title',       'like', $term)
                  ->orWhere('description','like', $term)
                  ->orWhere('root_cause', 'like', $term)
            );
        }

        return $query;
    }

    public function getStats(User $user, ?int $companyId = null): array
    {
        $base = CorrectiveMaintenance::query();
        $ids  = $user->accessibleCompanyIds();

        if ($companyId && in_array($companyId, $ids, true)) {
            $base->where('company_id', $companyId);
        } elseif (!$user->isSuperAdmin()) {
            count($ids) === 1
                ? $base->where('company_id', $ids[0])
                : $base->whereIn('company_id', $ids);
        }

        return [
            'total'       => (clone $base)->count(),
            'created'     => (clone $base)->where('status', CorrectiveMaintenance::STATUS_CREATED)->count(),
            'in_progress' => (clone $base)->where('status', CorrectiveMaintenance::STATUS_IN_PROGRESS)->count(),
            'completed'   => (clone $base)->where('status', CorrectiveMaintenance::STATUS_COMPLETED)->count(),
            'cancelled'   => (clone $base)->where('status', CorrectiveMaintenance::STATUS_CANCELLED)->count(),
            'urgent'      => (clone $base)->where('priority', CorrectiveMaintenance::PRIORITY_URGENT)
                                          ->whereIn('status', [
                                              CorrectiveMaintenance::STATUS_CREATED,
                                              CorrectiveMaintenance::STATUS_IN_PROGRESS,
                                          ])->count(),
            'unassigned'  => (clone $base)->whereNull('technician_id')
                                          ->whereIn('status', [
                                              CorrectiveMaintenance::STATUS_CREATED,
                                              CorrectiveMaintenance::STATUS_IN_PROGRESS,
                                          ])->count(),
        ];
    }

    // ── Notes ─────────────────────────────────────────────────────

    /**
     * Add a standalone chronological note without changing any field.
     * Logged as ACTION_NOTE_ADDED so it appears in the history timeline.
     */
    public function addNote(
        CorrectiveMaintenance $m,
        User                  $actor,
        string                $note,
    ): CorrectiveMaintenanceLog {
        $this->authorizeAccess($actor, $m);

        return $this->log($m, $actor,
            CorrectiveMaintenanceLog::ACTION_NOTE_ADDED,
            null, null, null, $note);
    }

    // ── Authorization ─────────────────────────────────────────────

    public function authorizeAccess(User $user, CorrectiveMaintenance $m): void
    {
        if (!$user->canAccessCompany($m->company_id)) {
            abort(403, 'You do not have access to this maintenance record.');
        }
    }

    // ── Internal helpers ──────────────────────────────────────────

    private function isAdmin(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->hasRole('tenant_admin');
    }

    private function log(
        CorrectiveMaintenance $m,
        User                  $actor,
        string                $action,
        ?string               $fieldName,
        ?string               $oldValue,
        ?string               $newValue,
        ?string               $note,
    ): CorrectiveMaintenanceLog {
        return CorrectiveMaintenanceLog::create([
            'maintenance_id' => $m->id,
            'changed_by'     => $actor->id,
            'action'         => $action,
            'field_name'     => $fieldName,
            'old_value'      => $oldValue,
            'new_value'      => $newValue,
            'note'           => $note,
        ]);
    }
}
