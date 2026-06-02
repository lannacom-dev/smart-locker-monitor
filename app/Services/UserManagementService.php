<?php

namespace App\Services;

use App\Models\PermissionAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserManagementService
{
    // Role numeric levels — higher = more privileged
    public const ROLE_LEVELS = [
        'super_admin'  => 100,
        'tenant_admin' => 50,
        'operator'     => 30,
        'technician'   => 30,
        'support'      => 10,
        'viewer'       => 10,
    ];

    // ── User CRUD ─────────────────────────────────────────────────

    public function createUser(array $data, User $actor): User
    {
        $this->authorizeCreate($actor, $data['role'] ?? null);

        return DB::transaction(function () use ($data, $actor) {
            $user = User::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => bcrypt($data['password']),
                'company_id' => $data['company_id'] ?? null,
                'is_active'  => $data['is_active'] ?? true,
            ]);

            if (! empty($data['role'])) {
                $user->assignRole($data['role']);
            }

            $this->log(
                actor:      $actor,
                action:     PermissionAuditLog::ACTION_USER_CREATED,
                targetType: 'user',
                targetId:   $user->id,
                targetName: $user->name,
                newValue:   $data['role'] ?? 'no role',
            );

            return $user;
        });
    }

    public function updateUser(User $target, array $data, User $actor): void
    {
        $this->authorize($actor, $target);

        $tracked  = ['name', 'email', 'company_id'];
        $oldState = array_intersect_key($target->only($tracked), array_flip($tracked));

        DB::transaction(function () use ($target, $data, $actor, $oldState) {
            $fields = array_intersect_key($data, array_flip(['name', 'email', 'company_id']));
            $target->update(array_filter($fields, fn ($v) => $v !== null));

            $newState = array_intersect_key($target->fresh()->only(array_keys($oldState)), array_flip(array_keys($oldState)));

            if ($oldState !== $newState) {
                $this->log(
                    actor:      $actor,
                    action:     PermissionAuditLog::ACTION_USER_UPDATED,
                    targetType: 'user',
                    targetId:   $target->id,
                    targetName: $target->name,
                    oldValue:   implode(', ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($oldState), $oldState)),
                    newValue:   implode(', ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($newState), $newState)),
                );
            }
        });
    }

    public function disableUser(User $target, User $actor): void
    {
        $this->authorize($actor, $target);

        if ($target->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            abort(403, 'Cannot disable a Super Admin account.');
        }

        if ($target->id === $actor->id) {
            abort(422, 'You cannot disable your own account.');
        }

        DB::transaction(function () use ($target, $actor) {
            $target->update(['is_active' => false]);
            $target->tokens()->delete();

            $this->log($actor, PermissionAuditLog::ACTION_USER_DISABLED, 'user', $target->id, $target->name);
        });
    }

    public function enableUser(User $target, User $actor): void
    {
        $this->authorize($actor, $target);

        DB::transaction(function () use ($target, $actor) {
            $target->update(['is_active' => true]);
            $this->log($actor, PermissionAuditLog::ACTION_USER_ENABLED, 'user', $target->id, $target->name);
        });
    }

    public function resetPassword(User $target, User $actor): string
    {
        $this->authorize($actor, $target);

        $plain = \Illuminate\Support\Str::random(12);

        DB::transaction(function () use ($target, $actor, $plain) {
            $target->update(['password' => bcrypt($plain)]);
            $target->tokens()->delete();

            $this->log($actor, PermissionAuditLog::ACTION_USER_PASSWORD_RESET, 'user', $target->id, $target->name);
        });

        return $plain; // caller displays once
    }

    // ── Role assignment ───────────────────────────────────────────

    /**
     * Replace all roles on $target with $roleNames.
     * Validates hierarchy: actor cannot assign a role >= their own level.
     */
    public function syncRoles(User $target, array $roleNames, User $actor, ?string $note = null): void
    {
        $this->authorize($actor, $target);

        foreach ($roleNames as $roleName) {
            if (! $this->canAssignRole($actor, $roleName)) {
                abort(403, "You cannot assign the '{$roleName}' role.");
            }
        }

        $oldRoles = $target->roles->pluck('name')->sort()->values()->implode(', ');

        DB::transaction(function () use ($target, $roleNames, $actor, $oldRoles, $note) {
            $target->syncRoles($roleNames);

            $this->log(
                actor:      $actor,
                action:     PermissionAuditLog::ACTION_ROLES_SYNCED,
                targetType: 'user',
                targetId:   $target->id,
                targetName: $target->name,
                oldValue:   $oldRoles ?: '(none)',
                newValue:   implode(', ', $roleNames) ?: '(none)',
                note:       $note,
            );
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ── Role-permission matrix ────────────────────────────────────

    public function updateRolePermissions(string $roleName, array $permissions, User $actor): void
    {
        if (! $actor->isSuperAdmin()) {
            abort(403, 'Only Super Admin can modify role permission sets.');
        }

        $role     = Role::findByName($roleName, 'web');
        $oldPerms = $role->permissions->pluck('name')->sort()->values()->implode(', ');

        DB::transaction(function () use ($role, $permissions, $actor, $oldPerms, $roleName) {
            $role->syncPermissions($permissions);

            $this->log(
                actor:      $actor,
                action:     PermissionAuditLog::ACTION_ROLE_PERMISSIONS_UPDATED,
                targetType: 'role',
                targetId:   $role->id,
                targetName: $roleName,
                oldValue:   $oldPerms ?: '(none)',
                newValue:   implode(', ', $permissions) ?: '(none)',
            );
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ── Queries ───────────────────────────────────────────────────

    public function getAccessibleUsers(User $actor): Builder
    {
        $query = User::with('roles', 'company')->orderBy('name');

        if ($actor->isSuperAdmin()) {
            return $query;
        }

        // Tenant admins never see super_admin accounts
        return $query
            ->whereIn('company_id', $actor->accessibleCompanyIds())
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'));
    }

    /** All roles the actor is allowed to assign (strictly lower level). */
    public function getAssignableRoles(User $actor): array
    {
        return array_values(array_filter(
            array_keys(self::ROLE_LEVELS),
            fn ($role) => $this->canAssignRole($actor, $role),
        ));
    }

    // ── Guards ────────────────────────────────────────────────────

    public function authorize(User $actor, User $target): void
    {
        if (! $actor->can('edit users')) {
            abort(403, 'You do not have permission to manage users.');
        }

        if ($actor->isSuperAdmin()) return;

        if ($target->isSuperAdmin()) {
            abort(403, 'You cannot manage a Super Admin account.');
        }

        if ($target->company_id && ! $actor->canAccessCompany($target->company_id)) {
            abort(403, 'You do not have access to this user.');
        }
    }

    public function authorizeCreate(User $actor, ?string $role): void
    {
        if (! $actor->can('create users')) {
            abort(403, 'You do not have permission to create users.');
        }

        if ($role && ! $this->canAssignRole($actor, $role)) {
            abort(403, "You cannot create a user with the '{$role}' role.");
        }
    }

    public function authorizeView(User $actor, User $target): void
    {
        if (! $actor->can('view users')) abort(403);
        if ($actor->isSuperAdmin()) return;
        if ($target->isSuperAdmin()) abort(403);
        if ($target->company_id && ! $actor->canAccessCompany($target->company_id)) abort(403);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function canAssignRole(User $actor, string $roleName): bool
    {
        if ($actor->isSuperAdmin()) return true;

        $actorLevel  = $actor->getHighestRoleLevel();
        $targetLevel = self::ROLE_LEVELS[$roleName] ?? 0;

        return $actorLevel > $targetLevel; // must be STRICTLY higher
    }

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
