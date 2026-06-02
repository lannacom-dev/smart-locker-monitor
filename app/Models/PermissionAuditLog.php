<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionAuditLog extends Model
{
    /** Immutable audit record — no updated_at column. */
    const UPDATED_AT = null;

    // ── Action constants ──────────────────────────────────────────

    const ACTION_USER_CREATED              = 'user.created';
    const ACTION_USER_UPDATED              = 'user.updated';
    const ACTION_USER_DISABLED             = 'user.disabled';
    const ACTION_USER_ENABLED              = 'user.enabled';
    const ACTION_USER_PASSWORD_RESET       = 'user.password_reset';
    const ACTION_ROLES_SYNCED              = 'roles.synced';
    const ACTION_ROLE_PERMISSIONS_UPDATED  = 'role.permissions.updated';

    // Locker user actions
    const ACTION_LOCKER_USER_CREATED  = 'locker_user.created';
    const ACTION_LOCKER_USER_UPDATED  = 'locker_user.updated';
    const ACTION_LOCKER_USER_DISABLED = 'locker_user.disabled';
    const ACTION_LOCKER_USER_ENABLED  = 'locker_user.enabled';

    // User type actions
    const ACTION_USER_TYPE_CREATED    = 'user_type.created';
    const ACTION_USER_TYPE_UPDATED    = 'user_type.updated';
    const ACTION_USER_TYPE_DISABLED   = 'user_type.disabled';
    const ACTION_USER_TYPE_ENABLED    = 'user_type.enabled';

    protected $fillable = [
        'causer_id',
        'action',
        'target_type',
        'target_id',
        'target_name',
        'old_value',
        'new_value',
        'note',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_USER_CREATED             => 'User Created',
            self::ACTION_USER_UPDATED             => 'Profile Updated',
            self::ACTION_USER_DISABLED            => 'User Disabled',
            self::ACTION_USER_ENABLED             => 'User Enabled',
            self::ACTION_USER_PASSWORD_RESET      => 'Password Reset',
            self::ACTION_ROLES_SYNCED             => 'Roles Changed',
            self::ACTION_ROLE_PERMISSIONS_UPDATED => 'Role Permissions Updated',
            self::ACTION_LOCKER_USER_CREATED      => 'Locker User Created',
            self::ACTION_LOCKER_USER_UPDATED      => 'Locker User Updated',
            self::ACTION_LOCKER_USER_DISABLED     => 'Locker User Disabled',
            self::ACTION_LOCKER_USER_ENABLED      => 'Locker User Enabled',
            self::ACTION_USER_TYPE_CREATED        => 'User Type Created',
            self::ACTION_USER_TYPE_UPDATED        => 'User Type Updated',
            self::ACTION_USER_TYPE_DISABLED       => 'User Type Disabled',
            self::ACTION_USER_TYPE_ENABLED        => 'User Type Enabled',
            default                               => ucfirst(str_replace(['.', '_'], ' ', $this->action)),
        };
    }

    public function actionColor(): string
    {
        return match ($this->action) {
            self::ACTION_USER_CREATED             => 'text-green-600 dark:text-green-400',
            self::ACTION_USER_DISABLED            => 'text-red-600 dark:text-red-400',
            self::ACTION_USER_ENABLED             => 'text-green-600 dark:text-green-400',
            self::ACTION_USER_PASSWORD_RESET      => 'text-amber-600 dark:text-amber-400',
            self::ACTION_ROLES_SYNCED             => 'text-blue-600 dark:text-blue-400',
            self::ACTION_ROLE_PERMISSIONS_UPDATED => 'text-purple-600 dark:text-purple-400',
            self::ACTION_LOCKER_USER_CREATED      => 'text-green-600 dark:text-green-400',
            self::ACTION_LOCKER_USER_UPDATED      => 'text-blue-600 dark:text-blue-400',
            self::ACTION_LOCKER_USER_DISABLED     => 'text-red-600 dark:text-red-400',
            self::ACTION_LOCKER_USER_ENABLED      => 'text-green-600 dark:text-green-400',
            self::ACTION_USER_TYPE_CREATED        => 'text-teal-600 dark:text-teal-400',
            self::ACTION_USER_TYPE_UPDATED        => 'text-blue-600 dark:text-blue-400',
            self::ACTION_USER_TYPE_DISABLED       => 'text-red-600 dark:text-red-400',
            self::ACTION_USER_TYPE_ENABLED        => 'text-green-600 dark:text-green-400',
            default                               => 'text-gray-600 dark:text-gray-400',
        };
    }

    public function actionIcon(): string
    {
        return match ($this->action) {
            self::ACTION_USER_CREATED             => 'heroicon-o-user-plus',
            self::ACTION_USER_DISABLED            => 'heroicon-o-no-symbol',
            self::ACTION_USER_ENABLED             => 'heroicon-o-check-circle',
            self::ACTION_USER_PASSWORD_RESET      => 'heroicon-o-key',
            self::ACTION_ROLES_SYNCED             => 'heroicon-o-shield-check',
            self::ACTION_ROLE_PERMISSIONS_UPDATED => 'heroicon-o-adjustments-horizontal',
            self::ACTION_LOCKER_USER_CREATED      => 'heroicon-o-user-plus',
            self::ACTION_LOCKER_USER_UPDATED      => 'heroicon-o-pencil-square',
            self::ACTION_LOCKER_USER_DISABLED     => 'heroicon-o-no-symbol',
            self::ACTION_LOCKER_USER_ENABLED      => 'heroicon-o-check-circle',
            self::ACTION_USER_TYPE_CREATED        => 'heroicon-o-tag',
            self::ACTION_USER_TYPE_UPDATED        => 'heroicon-o-pencil-square',
            self::ACTION_USER_TYPE_DISABLED       => 'heroicon-o-no-symbol',
            self::ACTION_USER_TYPE_ENABLED        => 'heroicon-o-check-circle',
            default                               => 'heroicon-o-pencil-square',
        };
    }

    /** Grouped permission labels for the role-permission matrix. */
    public static function permissionGroups(): array
    {
        return [
            'Dashboard'     => ['view dashboard'],
            'Companies'     => ['view companies', 'create companies', 'edit companies', 'delete companies'],
            'Locations'     => ['view locations', 'create locations', 'edit locations', 'delete locations'],
            'Lockers'       => ['view lockers', 'create lockers', 'edit lockers', 'delete lockers',
                                'view locker boxes', 'edit locker boxes', 'view locker events',
                                'unlock locker', 'restart locker', 'update locker status'],
            'Users'         => ['view users', 'create users', 'edit users', 'delete users'],
            'Reports'       => ['view reports', 'export reports'],
            'System Health' => ['view system health', 'acknowledge alerts'],
            'Issues'        => ['view issues', 'create issues', 'edit issues',
                                'assign issues', 'close issues', 'delete issues'],
            'Maintenance'   => ['view maintenance', 'create maintenance', 'edit maintenance',
                                'assign maintenance', 'complete maintenance',
                                'cancel maintenance', 'delete maintenance'],
            'Locker Users'  => ['view locker users', 'create locker users',
                                'edit locker users', 'disable locker users',
                                'manage user types'],
        ];
    }
}
