<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Location;
use App\Models\Locker;
use App\Models\LockerBox;
use App\Models\LockerEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SmartLockerDemoSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Super Admin
        |--------------------------------------------------------------------------
        */

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@smartlocker.local'],
            [
                'company_id' => null,
                'name' => 'System Admin',
                'password' => Hash::make('password1234'),
                'is_active' => true,
            ]
        );

        if (method_exists($superAdmin, 'assignRole')) {
            $superAdmin->syncRoles(['super_admin']);
        }

        /*
        |--------------------------------------------------------------------------
        | Company 1: Lannacom
        |--------------------------------------------------------------------------
        */

        $lannacom = Company::updateOrCreate(
            ['code' => 'LANNACOM'],
            [
                'parent_company_id' => null,   // Root reseller
                'name' => 'Lannacom Co., Ltd.',
                'contact_name' => 'Lannacom Contact',
                'contact_phone' => '+66 53 441480',
                'is_active' => true,
            ]
        );

        $lannacomLocation = Location::updateOrCreate(
            [
                'company_id' => $lannacom->id,
                'name' => 'Lannacom Head Office',
            ],
            [
                'address' => '125 Moo.6 T.Hangdong A.Hangdong Chiang Mai 50230',
                'latitude' => null,
                'longitude' => null,
                'is_active' => true,
            ]
        );

        $lannacomAdmin = User::updateOrCreate(
            ['email' => 'admin@lanna.co.th'],
            [
                'company_id' => $lannacom->id,
                'name' => 'Lannacom Admin',
                'password' => Hash::make('password1234'),
                'is_active' => true,
            ]
        );

        if (method_exists($lannacomAdmin, 'assignRole')) {
            $lannacomAdmin->syncRoles(['tenant_admin']);
        }

        $lannacomViewer = User::updateOrCreate(
            ['email' => 'viewer@lanna.co.th'],
            [
                'company_id' => $lannacom->id,
                'name' => 'Lannacom Viewer',
                'password' => Hash::make('password1234'),
                'is_active' => true,
            ]
        );

        if (method_exists($lannacomViewer, 'assignRole')) {
            $lannacomViewer->syncRoles(['viewer']);
        }

        $this->createLockerSet(
            company: $lannacom,
            location: $lannacomLocation,
            lockerName: 'Lanna Locker 01',
            serialNumber: 'LANNACOM-NEXA-001',
            ipAddress: '10.10.70.213',
            boxCount: 12,
            status: 'available'
        );

        $this->createLockerSet(
            company: $lannacom,
            location: $lannacomLocation,
            lockerName: 'Lanna Locker 02',
            serialNumber: 'LANNACOM-NEXA-002',
            ipAddress: '10.10.70.214',
            boxCount: 8,
            status: 'disabled'
        );

        /*
        |--------------------------------------------------------------------------
        | Company 2: Nexastone
        |--------------------------------------------------------------------------
        */

        $nexastone = Company::updateOrCreate(
            ['code' => 'NEXASTONE'],
            [
                'parent_company_id' => $lannacom->id,  // Reseller of Lannacom
                'name' => 'Nexastone Company Limited',
                'contact_name' => 'Nexastone Contact',
                'contact_phone' => '021238822',
                'is_active' => true,
            ]
        );

        $nexastoneLocation = Location::updateOrCreate(
            [
                'company_id' => $nexastone->id,
                'name' => 'Nexastone Head Office',
            ],
            [
                'address' => '300 Asoke-Dindaeng Rd, Huaykwang, Bangkok 10310 Thailand',
                'latitude' => null,
                'longitude' => null,
                'is_active' => true,
            ]
        );

        $nexastoneAdmin = User::updateOrCreate(
            ['email' => 'admin@nexastone.local'],
            [
                'company_id' => $nexastone->id,
                'name' => 'Nexastone Admin',
                'password' => Hash::make('password1234'),
                'is_active' => true,
            ]
        );

        if (method_exists($nexastoneAdmin, 'assignRole')) {
            $nexastoneAdmin->syncRoles(['tenant_admin']);
        }

        $this->createLockerSet(
            company: $nexastone,
            location: $nexastoneLocation,
            lockerName: 'Nexastone Locker 01',
            serialNumber: 'NEXASTONE-NEXA-001',
            ipAddress: '10.20.30.101',
            boxCount: 16,
            status: 'in_use'
        );

        /*
        |--------------------------------------------------------------------------
        | Company 3: Dynatix
        |--------------------------------------------------------------------------
        */

        $dynatix = Company::updateOrCreate(
            ['code' => 'DYNATIX'],
            [
                'parent_company_id' => $nexastone->id,  // Reseller of Nexastone
                'name' => 'บริษัท ไดนาทิกซ์ จำกัด',
                'contact_name' => 'Dynatix Contact',
                'contact_phone' => null,
                'is_active' => true,
            ]
        );

        $dynatixLocation = Location::updateOrCreate(
            [
                'company_id' => $dynatix->id,
                'name' => 'Dynatix Head Office',
            ],
            [
                'address' => '89, อาคาร เอไอเอ แคปปิตอล เซ็นเตอร์ ถนนรัชดาภิเษก แขวงดินแดง เขตดินแดง กรุงเทพมหานคร 10400',
                'latitude' => null,
                'longitude' => null,
                'is_active' => true,
            ]
        );

        $dynatixAdmin = User::updateOrCreate(
            ['email' => 'admin@dynatix.local'],
            [
                'company_id' => $dynatix->id,
                'name' => 'Dynatix Admin',
                'password' => Hash::make('password1234'),
                'is_active' => true,
            ]
        );

        if (method_exists($dynatixAdmin, 'assignRole')) {
            $dynatixAdmin->syncRoles(['tenant_admin']);
        }

        $this->createLockerSet(
            company: $dynatix,
            location: $dynatixLocation,
            lockerName: 'Dynatix Locker 01',
            serialNumber: 'DYNATIX-NEXA-001',
            ipAddress: '10.30.40.101',
            boxCount: 10,
            status: 'fault'
        );
    }

    private function createLockerSet(
        Company $company,
        Location $location,
        string $lockerName,
        string $serialNumber,
        string $ipAddress,
        int $boxCount,
        string $status = 'available'
    ): void {
        $online = in_array($status, ['available', 'in_use']);
        $locker = Locker::updateOrCreate(
            ['serial_number' => $serialNumber],
            [
                'company_id' => $company->id,
                'location_id' => $location->id,
                'name' => $lockerName,
                'api_token' => hash('sha256', $serialNumber . '-token'),
                'ip_address' => $ipAddress,
                'status' => $status,
                'last_seen_at' => $online ? now()->subSeconds(rand(10, 90)) : now()->subMinutes(rand(5, 60)),
                'firmware_version' => '1.0.' . rand(1, 9),
                'description' => 'Demo smart locker for ' . $company->name,
                'is_active' => true,
            ]
        );

        for ($i = 1; $i <= $boxCount; $i++) {
            $status = $this->getBoxStatus($i);

            $box = LockerBox::updateOrCreate(
                [
                    'locker_id' => $locker->id,
                    'box_number' => $i,
                ],
                [
                    'company_id' => $company->id,
                    'status' => $status,
                    'last_opened_at' => in_array($status, ['open', 'occupied'])
                        ? now()->subMinutes(rand(5, 240))
                        : null,
                    'is_active' => $status !== 'disabled',
                ]
            );

            LockerEvent::create([
                'company_id' => $company->id,
                'locker_id' => $locker->id,
                'locker_box_id' => $box->id,
                'event_type' => $status === 'error' ? 'error' : 'sync',
                'payload' => [
                    'box_number' => $box->box_number,
                    'status' => $status,
                    'source' => 'demo_seeder',
                ],
            ]);
        }

        LockerEvent::create([
            'company_id' => $company->id,
            'locker_id' => $locker->id,
            'locker_box_id' => null,
            'event_type' => 'heartbeat',
            'payload' => [
                'serial_number' => $locker->serial_number,
                'ip_address' => $locker->ip_address,
                'status' => $locker->status,
                'firmware_version' => $locker->firmware_version,
                'last_seen_at' => optional($locker->last_seen_at)->toDateTimeString(),
                'source' => 'demo_seeder',
            ],
        ]);
    }

    private function getBoxStatus(int $boxNumber): string
    {
        return match (true) {
            $boxNumber % 11 === 0 => 'disabled',
            $boxNumber % 7 === 0 => 'error',
            $boxNumber % 5 === 0 => 'open',
            $boxNumber % 3 === 0 => 'occupied',
            default => 'available',
        };
    }
}
