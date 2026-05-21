<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            [
                'email' => 'admin@smartlocker.local',
            ],
            [
                'company_id' => null,
                'name' => 'System Admin',
                'password' => Hash::make('password1234'),
                'is_active' => true,
            ]
        );

        $admin->assignRole('super_admin');
    }

    // superadmin lcdev@lannna.co.th
}