<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',

            'view companies',
            'create companies',
            'edit companies',
            'delete companies',

            'view locations',
            'create locations',
            'edit locations',
            'delete locations',

            'view lockers',
            'create lockers',
            'edit lockers',
            'delete lockers',

            'view locker boxes',
            'edit locker boxes',

            'view locker events',

            'unlock locker',
            'restart locker',

            'view users',
            'create users',
            'edit users',
            'delete users',

            'view reports',
            'export reports',

            'update locker status',

            // System Health Dashboard
            'view system health',
            'acknowledge alerts',

            // Issue Tracking
            'view issues',
            'create issues',
            'edit issues',
            'assign issues',
            'close issues',
            'delete issues',

            // Corrective Maintenance
            'view maintenance',
            'create maintenance',
            'edit maintenance',
            'assign maintenance',
            'complete maintenance',
            'cancel maintenance',
            'delete maintenance',

            // Locker Users
            'view locker users',
            'create locker users',
            'edit locker users',
            'disable locker users',
            'manage user types',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $tenantAdmin = Role::firstOrCreate([
            'name' => 'tenant_admin',
            'guard_name' => 'web',
        ]);

        $technician = Role::firstOrCreate([
            'name' => 'technician',
            'guard_name' => 'web',
        ]);

        $viewer = Role::firstOrCreate([
            'name' => 'viewer',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions($permissions);

        $tenantAdmin->syncPermissions([
            'view dashboard',

            'view locations',

            'view lockers',
            'edit lockers',

            'view locker boxes',
            'edit locker boxes',

            'view locker events',

            'view users',
            'create users',
            'edit users',

            'view reports',
            'export reports',

            'update locker status',

            // Health Dashboard
            'view system health',
            'acknowledge alerts',

            // Issues
            'view issues',
            'create issues',
            'edit issues',
            'assign issues',
            'close issues',

            // Maintenance
            'view maintenance',
            'create maintenance',
            'edit maintenance',
            'assign maintenance',
            'complete maintenance',
            'cancel maintenance',

            // Locker Users
            'view locker users',
            'create locker users',
            'edit locker users',
            'disable locker users',
        ]);

        $technician->syncPermissions([
            'view dashboard',
            'view locker users',

            'view lockers',
            'edit lockers',

            'view locker boxes',
            'edit locker boxes',

            'view locker events',

            'unlock locker',
            'restart locker',

            // Technician can view health but not acknowledge
            'view system health',

            // Technician can view/create/edit/assign issues
            'view issues',
            'create issues',
            'edit issues',
            'assign issues',

            // Technician handles maintenance (assigned to them)
            'view maintenance',
            'create maintenance',
            'edit maintenance',
            'complete maintenance',
        ]);

        $viewer->syncPermissions([
            'view dashboard',

            'view lockers',
            'view locker boxes',
            'view locker events',

            'view reports',

            // Viewer can only see issues and maintenance
            'view issues',
            'view maintenance',
        ]);

        // ── New roles ─────────────────────────────────────────────

        $operator = Role::firstOrCreate([
            'name'       => 'operator',
            'guard_name' => 'web',
        ]);

        $support = Role::firstOrCreate([
            'name'       => 'support',
            'guard_name' => 'web',
        ]);

        // Operator: full locker operations + issue/maintenance handling + locker users
        $operator->syncPermissions([
            'view dashboard',

            'view lockers',
            'edit lockers',

            'view locker boxes',
            'edit locker boxes',

            'view locker events',

            'unlock locker',
            'restart locker',
            'update locker status',

            'view system health',

            'view issues',
            'create issues',
            'edit issues',
            'assign issues',

            'view maintenance',
            'create maintenance',
            'edit maintenance',
            'complete maintenance',

            // Locker Users
            'view locker users',
            'create locker users',
            'edit locker users',
        ]);

        // Support: view + help customers, create and update issues only
        $support->syncPermissions([
            'view dashboard',

            'view lockers',
            'view locker boxes',
            'view locker events',

            'view system health',

            'view reports',

            'view issues',
            'create issues',
            'edit issues',

            'view maintenance',

            // Support can view locker users for customer assistance
            'view locker users',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
