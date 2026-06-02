<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;

class LockerUserTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug'        => UserType::SLUG_EMPLOYEE,
                'name'        => 'Employee',
                'description' => 'Internal company employee with standard locker access. Employee ID required.',
                'is_system'   => true,
                'is_active'   => true,
                'company_id'  => null,
            ],
            [
                'slug'        => UserType::SLUG_VISITOR,
                'name'        => 'Visitor',
                'description' => 'Temporary visitor with time-limited locker access. Access end date recommended.',
                'is_system'   => true,
                'is_active'   => true,
                'company_id'  => null,
            ],
            [
                'slug'        => UserType::SLUG_DELIVERY,
                'name'        => 'Delivery',
                'description' => 'Delivery personnel. Organization name required for tracking.',
                'is_system'   => true,
                'is_active'   => true,
                'company_id'  => null,
            ],
            [
                'slug'        => UserType::SLUG_TENANT_USER,
                'name'        => 'Tenant User',
                'description' => 'User associated with a tenant company, accessing shared locker space.',
                'is_system'   => true,
                'is_active'   => true,
                'company_id'  => null,
            ],
            [
                'slug'        => UserType::SLUG_EXTERNAL_USER,
                'name'        => 'External User',
                'description' => 'External party with supervised locker access. Organization name required.',
                'is_system'   => true,
                'is_active'   => true,
                'company_id'  => null,
            ],
        ];

        foreach ($types as $type) {
            UserType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }

        $this->command->info('  Seeded ' . count($types) . ' system user types.');
    }
}
