<?php

namespace App\Services;

use App\Models\Locker;
use App\Models\LockerEditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LockerEditService
{
    /**
     * Fields tracked in the audit log.
     * Sensitive fields (super_admin only) are included here; the caller must
     * have already validated permissions before passing them.
     */
    private const TRACKABLE_FIELDS = [
        'name', 'code', 'zone', 'floor', 'location_id', 'tenant_id',
        'status', 'description', 'is_active', 'ip_address',
        'serial_number', 'firmware_version',
        'heartbeat_interval', 'offline_after',
    ];

    /** Fields only super_admin may change. */
    private const SENSITIVE_FIELDS = [
        'serial_number', 'api_token', 'external_locker_id', 'external_unit_id', 'company_id',
    ];

    // ── Main update method ────────────────────────────────────────

    /**
     * Update allowed fields on $locker, writing one audit log entry per changed field.
     *
     * @param  array<string, mixed>  $fields  Validated field→value pairs (must NOT contain 'note')
     * @param  string|null           $note    Optional audit note shared across all log entries
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException  on 403
     */
    public function updateFields(Locker $locker, User $actor, array $fields, ?string $note = null): void
    {
        $this->authorize($actor, $locker);

        // Guard sensitive fields
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (array_key_exists($field, $fields) && ! $actor->isSuperAdmin()) {
                abort(403, "Only Super Admin can edit '{$field}'.");
            }
        }

        DB::transaction(function () use ($locker, $actor, $fields, $note) {
            $logs = [];

            foreach ($fields as $field => $newValue) {
                if (! in_array($field, self::TRACKABLE_FIELDS, true)) {
                    continue; // skip non-tracked fields (e.g. 'note')
                }

                $oldValue = $locker->getAttribute($field);

                // Cast booleans for consistent comparison
                $oldCast = is_bool($oldValue) ? (int) $oldValue : $oldValue;
                $newCast = is_bool($newValue) ? (int) $newValue : $newValue;

                if ((string) $oldCast === (string) $newCast) {
                    continue; // no change — skip
                }

                $logs[] = [
                    'locker_id'  => $locker->id,
                    'changed_by' => $actor->id,
                    'field_name' => $field,
                    'old_value'  => $oldValue !== null ? (string) $oldValue : null,
                    'new_value'  => $newValue !== null ? (string) $newValue : null,
                    'note'       => $note,
                    'created_at' => now(),
                ];
            }

            if (! empty($logs)) {
                LockerEditLog::insert($logs);
            }

            $locker->update($fields);
        });
    }

    // ── Access guard ──────────────────────────────────────────────

    public function authorize(User $actor, Locker $locker): void
    {
        if (! $actor->can('edit lockers')) {
            abort(403, 'You do not have permission to edit lockers.');
        }

        if (! $actor->canAccessCompany($locker->company_id)) {
            abort(403, 'You do not have access to this locker.');
        }
    }

    public function authorizeView(User $actor, Locker $locker): void
    {
        if (! $actor->can('view lockers')) {
            abort(403, 'You do not have permission to view lockers.');
        }

        if (! $actor->canAccessCompany($locker->company_id)) {
            abort(403, 'You do not have access to this locker.');
        }
    }

    // ── Query helpers ─────────────────────────────────────────────

    public function getEditLogs(Locker $locker, int $limit = 50)
    {
        return $locker->editLogs()
            ->with('changedBy')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
