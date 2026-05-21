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
        ]);

        $technician->syncPermissions([
            'view dashboard',

            'view lockers',
            'edit lockers',

            'view locker boxes',
            'edit locker boxes',

            'view locker events',

            'unlock locker',
            'restart locker',
        ]);

        $viewer->syncPermissions([
            'view dashboard',

            'view lockers',
            'view locker boxes',
            'view locker events',

            'view reports',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
