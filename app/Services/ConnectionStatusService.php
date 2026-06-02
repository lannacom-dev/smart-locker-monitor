<?php

namespace App\Services;

use App\Models\Locker;
use App\Models\LockerConnection;
use App\Models\LockerLocation;
use App\Models\LockerLocationLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConnectionStatusService
{
    // ──────────────────────────────────────────────────────────────
    // Heartbeat
    // ──────────────────────────────────────────────────────────────

    /**
     * Process an incoming heartbeat from a locker device.
     * Updates last_seen_at and recalculates connection_status.
     * Returns whether the connection_status actually changed.
     */
    public function processHeartbeat(
        Locker  $locker,
        ?string $firmwareVersion = null,
        ?string $ipAddress       = null,
        ?array  $extra           = null     // any additional payload fields
    ): bool {
        $oldConnStatus = $locker->connection_status;

        $updates = ['last_seen_at' => now()];

        if ($firmwareVersion !== null) {
            $updates['firmware_version'] = $firmwareVersion;
        }

        if ($ipAddress !== null) {
            $updates['ip_address'] = $ipAddress;
        }

        $locker->update($updates);
        $locker->refresh();

        $newConnStatus = $locker->computeConnectionStatus();

        $changed = $newConnStatus !== $oldConnStatus;

        if ($changed) {
            $this->writeConnectionLog(
                locker:    $locker,
                oldStatus: $oldConnStatus,
                newStatus: $newConnStatus,
                source:    LockerConnection::SOURCE_HEARTBEAT,
            );

            $locker->update(['connection_status' => $newConnStatus]);
        }

        return $changed;
    }

    // ──────────────────────────────────────────────────────────────
    // Offline sweep (called by artisan command)
    // ──────────────────────────────────────────────────────────────

    /**
     * Mark lockers as OFFLINE or WARNING when heartbeat is overdue.
     * Designed to run every minute via scheduler.
     *
     * Returns the number of lockers whose status changed.
     */
    public function sweepStaleLockers(): int
    {
        $count = 0;

        // Query only active lockers that are NOT already offline
        Locker::query()
            ->where('is_active', true)
            ->where('connection_status', '!=', LockerConnection::STATUS_OFFLINE)
            ->with([])
            ->chunk(200, function ($lockers) use (&$count) {
                foreach ($lockers as $locker) {
                    $computed = $locker->computeConnectionStatus();

                    if ($computed === $locker->connection_status) {
                        continue;
                    }

                    $this->writeConnectionLog(
                        locker:    $locker,
                        oldStatus: $locker->connection_status,
                        newStatus: $computed,
                        source:    LockerConnection::SOURCE_COMMAND,
                        reason:    'No heartbeat received within threshold.',
                    );

                    $locker->update(['connection_status' => $computed]);

                    $count++;
                }
            });

        return $count;
    }

    // ──────────────────────────────────────────────────────────────
    // Manual override
    // ──────────────────────────────────────────────────────────────

    /**
     * Force-set a locker's connection_status (admin override).
     */
    public function forceStatus(
        Locker  $locker,
        string  $newStatus,
        string  $source = LockerConnection::SOURCE_MANUAL,
        ?string $reason = null
    ): void {
        $this->validateConnectionStatus($newStatus);

        $oldStatus = $locker->connection_status;

        if ($oldStatus === $newStatus) {
            return;
        }

        $this->writeConnectionLog($locker, $oldStatus, $newStatus, $source, $reason);
        $locker->update(['connection_status' => $newStatus]);
    }

    // ──────────────────────────────────────────────────────────────
    // Floor plan position
    // ──────────────────────────────────────────────────────────────

    /**
     * Place (or move) a locker on a floor plan.
     * Writes an audit entry to locker_location_logs.
     */
    public function placeLocker(
        Locker   $locker,
        int      $floorPlanId,
        float    $posX,
        float    $posY,
        ?string  $zone     = null,
        ?string  $note     = null,
        ?int     $placedBy = null,
        ?string  $reason   = null
    ): LockerLocation {
        return DB::transaction(function () use (
            $locker, $floorPlanId, $posX, $posY, $zone, $note, $placedBy, $reason
        ) {
            $existing = LockerLocation::where('locker_id', $locker->id)->first();

            // Capture old values for audit
            $oldFloorPlanId = $existing?->floor_plan_id;
            $oldX           = $existing?->pos_x;
            $oldY           = $existing?->pos_y;

            $position = LockerLocation::updateOrCreate(
                ['locker_id' => $locker->id],
                [
                    'company_id'   => $locker->company_id,
                    'floor_plan_id'=> $floorPlanId,
                    'pos_x'        => round($posX, 3),
                    'pos_y'        => round($posY, 3),
                    'zone'         => $zone,
                    'note'         => $note,
                    'placed_by'    => $placedBy,
                ]
            );

            // Audit log
            DB::table('locker_location_logs')->insert([
                'company_id'      => $locker->company_id,
                'locker_id'       => $locker->id,
                'floor_plan_id'   => $floorPlanId,
                'old_floor_plan_id'=> $oldFloorPlanId,
                'old_pos_x'       => $oldX,
                'old_pos_y'       => $oldY,
                'new_pos_x'       => round($posX, 3),
                'new_pos_y'       => round($posY, 3),
                'changed_by'      => $placedBy,
                'reason'          => $reason,
                'created_at'      => now(),
            ]);

            return $position;
        });
    }

    /**
     * Remove a locker from any floor plan and log the removal.
     */
    public function removeLockerFromFloorPlan(
        Locker  $locker,
        ?int    $removedBy = null,
        ?string $reason    = null
    ): void {
        $existing = LockerLocation::where('locker_id', $locker->id)->first();

        if (! $existing) {
            return;
        }

        DB::table('locker_location_logs')->insert([
            'company_id'       => $locker->company_id,
            'locker_id'        => $locker->id,
            'floor_plan_id'    => null,
            'old_floor_plan_id'=> $existing->floor_plan_id,
            'old_pos_x'        => $existing->pos_x,
            'old_pos_y'        => $existing->pos_y,
            'new_pos_x'        => null,
            'new_pos_y'        => null,
            'changed_by'       => $removedBy,
            'reason'           => $reason ?? 'Removed from floor plan.',
            'created_at'       => now(),
        ]);

        $existing->delete();
    }

    // ──────────────────────────────────────────────────────────────
    // Query helpers
    // ──────────────────────────────────────────────────────────────

    /** Builder for floor-plan lockers filtered by connection/operational status, zone, etc. */
    public function lockersOnFloorPlan(
        int     $floorPlanId,
        ?string $connectionStatus = null,
        ?string $operationalStatus = null,
        ?string $zone             = null
    ): Builder {
        return Locker::query()
            ->join('locker_locations', 'locker_locations.locker_id', '=', 'lockers.id')
            ->where('locker_locations.floor_plan_id', $floorPlanId)
            ->when($connectionStatus, fn($q) => $q->where('lockers.connection_status', $connectionStatus))
            ->when($operationalStatus, fn($q) => $q->where('lockers.status', $operationalStatus))
            ->when($zone, fn($q) => $q->where('locker_locations.zone', $zone))
            ->select([
                'lockers.*',
                'locker_locations.pos_x',
                'locker_locations.pos_y',
                'locker_locations.zone as floor_zone',
                'locker_locations.note as floor_note',
                'locker_locations.id as locker_location_id',
            ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Internal
    // ──────────────────────────────────────────────────────────────

    private function writeConnectionLog(
        Locker  $locker,
        ?string $oldStatus,
        string  $newStatus,
        string  $source,
        ?string $reason = null
    ): void {
        LockerConnection::create([
            'company_id' => $locker->company_id,
            'locker_id'  => $locker->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'source'     => $source,
            'reason'     => $reason,
        ]);

        Log::info('Locker connection status changed', [
            'locker_id'  => $locker->id,
            'locker_name'=> $locker->name,
            'old'        => $oldStatus,
            'new'        => $newStatus,
            'source'     => $source,
        ]);
    }

    private function validateConnectionStatus(string $status): void
    {
        $valid = [
            LockerConnection::STATUS_ONLINE,
            LockerConnection::STATUS_WARNING,
            LockerConnection::STATUS_OFFLINE,
        ];

        if (! in_array($status, $valid, true)) {
            throw new \InvalidArgumentException("Invalid connection status: {$status}");
        }
    }
}
