<?php

namespace App\Services;

use App\Models\Company;
use App\Models\LockerUser;
use App\Models\PermissionAuditLog;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LockerUserService
{
    // ── User Type CRUD ────────────────────────────────────────────

    public function createUserType(array $data, User $actor): UserType
    {
        $this->authorizeManageUserTypes($actor);

        return DB::transaction(function () use ($data, $actor) {
            $type = UserType::create([
                'company_id'  => $data['company_id'] ?? null,
                'name'        => $data['name'],
                'slug'        => $data['slug'],
                'description' => $data['description'] ?? null,
                'is_system'   => false,
                'is_active'   => $data['is_active'] ?? true,
            ]);

            $this->log(
                actor:      $actor,
                action:     PermissionAuditLog::ACTION_USER_TYPE_CREATED,
                targetType: 'user_type',
                targetId:   $type->id,
                targetName: $type->name,
                newValue:   $type->slug,
            );

            return $type;
        });
    }

    public function updateUserType(UserType $type, array $data, User $actor): void
    {
        $this->authorizeManageUserTypes($actor);

        $tracked  = ['name', 'slug', 'description', 'is_active'];
        $oldState = $type->only($tracked);

        DB::transaction(function () use ($type, $data, $actor, $oldState, $tracked) {
            $fields = array_intersect_key($data, array_flip($tracked));
            $type->update($fields);

            $newState = $type->fresh()->only($tracked);

            if ($oldState !== $newState) {
                $this->log(
                    actor:      $actor,
                    action:     PermissionAuditLog::ACTION_USER_TYPE_UPDATED,
                    targetType: 'user_type',
                    targetId:   $type->id,
                    targetName: $type->name,
                    oldValue:   implode(', ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($oldState), $oldState)),
                    newValue:   implode(', ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($newState), $newState)),
                );
            }
        });
    }

    public function disableUserType(UserType $type, User $actor): void
    {
        $this->authorizeManageUserTypes($actor);

        if ($type->is_system) {
            abort(422, 'System user types cannot be deactivated.');
        }

        DB::transaction(function () use ($type, $actor) {
            $type->update(['is_active' => false]);
            $this->log($actor, PermissionAuditLog::ACTION_USER_TYPE_DISABLED, 'user_type', $type->id, $type->name);
        });
    }

    public function enableUserType(UserType $type, User $actor): void
    {
        $this->authorizeManageUserTypes($actor);

        DB::transaction(function () use ($type, $actor) {
            $type->update(['is_active' => true]);
            $this->log($actor, PermissionAuditLog::ACTION_USER_TYPE_ENABLED, 'user_type', $type->id, $type->name);
        });
    }

    // ── Locker User CRUD ──────────────────────────────────────────

    public function createLockerUser(array $data, User $actor): LockerUser
    {
        $this->authorizeCreateLockerUser($actor, (int) $data['company_id']);

        return DB::transaction(function () use ($data, $actor) {
            $lockerUser = LockerUser::create([
                'company_id'        => $data['company_id'],
                'user_type_id'      => $data['user_type_id'],
                'created_by'        => $actor->id,
                'updated_by'        => $actor->id,
                'full_name'         => $data['full_name'],
                'email'             => $data['email'] ?? null,
                'phone'             => $data['phone'] ?? null,
                'employee_id'       => $data['employee_id'] ?? null,
                'organization'      => $data['organization'] ?? null,
                'is_active'         => $data['is_active'] ?? true,
                'access_start_date' => $data['access_start_date'] ?? null,
                'access_end_date'   => $data['access_end_date'] ?? null,
            ]);

            $type = UserType::find($data['user_type_id']);

            $this->log(
                actor:      $actor,
                action:     PermissionAuditLog::ACTION_LOCKER_USER_CREATED,
                targetType: 'locker_user',
                targetId:   $lockerUser->id,
                targetName: $lockerUser->full_name,
                newValue:   $type?->name ?? 'unknown type',
            );

            return $lockerUser;
        });
    }

    public function updateLockerUser(LockerUser $lockerUser, array $data, User $actor): void
    {
        $this->authorizeEditLockerUser($actor, $lockerUser);

        $tracked  = ['full_name', 'email', 'phone', 'employee_id', 'organization',
                     'user_type_id', 'is_active', 'access_start_date', 'access_end_date'];
        $oldState = $lockerUser->only($tracked);

        DB::transaction(function () use ($lockerUser, $data, $actor, $oldState, $tracked) {
            $fields = array_intersect_key($data, array_flip($tracked));
            $fields['updated_by'] = $actor->id;
            $lockerUser->update($fields);

            $newState = $lockerUser->fresh()->only($tracked);

            if ($oldState !== $newState) {
                // Build human-readable diff
                $diffs = [];
                foreach ($tracked as $field) {
                    $old = $oldState[$field] ?? null;
                    $new = $newState[$field] ?? null;
                    if ($old != $new) {
                        $diffs[] = "{$field}: " . ($old ?? '—') . " → " . ($new ?? '—');
                    }
                }

                $this->log(
                    actor:      $actor,
                    action:     PermissionAuditLog::ACTION_LOCKER_USER_UPDATED,
                    targetType: 'locker_user',
                    targetId:   $lockerUser->id,
                    targetName: $lockerUser->full_name,
                    oldValue:   implode(', ', array_map(fn ($k, $v) => "{$k}: " . ($v ?? '—'), array_keys($oldState), $oldState)),
                    newValue:   implode(', ', $diffs),
                );
            }
        });
    }

    public function disableLockerUser(LockerUser $lockerUser, User $actor): void
    {
        $this->authorizeEditLockerUser($actor, $lockerUser);

        DB::transaction(function () use ($lockerUser, $actor) {
            $lockerUser->update(['is_active' => false]);
            $this->log($actor, PermissionAuditLog::ACTION_LOCKER_USER_DISABLED, 'locker_user', $lockerUser->id, $lockerUser->full_name);
        });
    }

    public function enableLockerUser(LockerUser $lockerUser, User $actor): void
    {
        $this->authorizeEditLockerUser($actor, $lockerUser);

        DB::transaction(function () use ($lockerUser, $actor) {
            $lockerUser->update(['is_active' => true]);
            $this->log($actor, PermissionAuditLog::ACTION_LOCKER_USER_ENABLED, 'locker_user', $lockerUser->id, $lockerUser->full_name);
        });
    }

    // ── Queries ───────────────────────────────────────────────────

    /** Base query for user types visible to the actor. */
    public function getAccessibleUserTypes(User $actor): Builder
    {
        return UserType::with('company')
            ->forActor($actor)
            ->orderBy('is_system', 'desc')
            ->orderBy('name');
    }

    /** Base query for locker users accessible to the actor. */
    public function getAccessibleLockerUsers(User $actor): Builder
    {
        return LockerUser::with(['company', 'userType'])
            ->whereIn('company_id', $actor->accessibleCompanyIds())
            ->orderBy('full_name');
    }

    // ── Guards ────────────────────────────────────────────────────

    public function authorizeManageUserTypes(User $actor): void
    {
        if (! $actor->can('manage user types')) {
            abort(403, 'You do not have permission to manage user types.');
        }
    }

    public function authorizeViewLockerUsers(User $actor): void
    {
        if (! $actor->can('view locker users')) {
            abort(403, 'You do not have permission to view locker users.');
        }
    }

    public function authorizeCreateLockerUser(User $actor, int $companyId): void
    {
        if (! $actor->can('create locker users')) {
            abort(403, 'You do not have permission to create locker users.');
        }

        if (! $actor->canAccessCompany($companyId)) {
            abort(403, 'You do not have access to this company.');
        }
    }

    public function authorizeEditLockerUser(User $actor, LockerUser $lockerUser): void
    {
        if (! $actor->can('edit locker users') && ! $actor->can('view locker users')) {
            abort(403, 'You do not have permission to manage locker users.');
        }

        if (! $actor->canAccessCompany($lockerUser->company_id)) {
            abort(403, 'You do not have access to this locker user.');
        }
    }

    // ── Private ───────────────────────────────────────────────────

    private function log(
        User    $actor,
        string  $action,
        string  $targetType,
        int     $targetId,
        string  $targetName,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $note     = null,
    ): void {
        PermissionAuditLog::create([
            'causer_id'   => $actor->id,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'target_name' => $targetName,
            'old_value'   => $oldValue,
            'new_value'   => $newValue,
            'note'        => $note,
            'ip_address'  => request()?->ip(),
        ]);
    }
}
