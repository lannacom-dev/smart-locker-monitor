<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\FloorPlan;
use App\Models\Locker;
use App\Models\LockerConnection;
use App\Models\LockerLocation;
use App\Services\ConnectionStatusService;
use Illuminate\Database\Seeder;

class FloorPlanSeeder extends Seeder
{
    public function run(): void
    {
        /** @var ConnectionStatusService $svc */
        $svc = app(ConnectionStatusService::class);

        $lannacom   = Company::where('code', 'LANNACOM')->first();
        $nexastone  = Company::where('code', 'NEXASTONE')->first();
        $dynatix    = Company::where('code', 'DYNATIX')->first();

        // ── Lannacom: 2 floor plans ────────────────────────────────────
        if ($lannacom) {
            $location = $lannacom->locations()->first();

            $fp1 = FloorPlan::updateOrCreate(
                ['company_id' => $lannacom->id, 'name' => 'Lannacom HQ – Floor 1'],
                [
                    'location_id'  => $location?->id,
                    'building'     => 'HQ Building',
                    'floor'        => '1',
                    'zone'         => 'Main Office',
                    'image_url'    => null,   // blank canvas for demo
                    'is_active'    => true,
                ]
            );

            $fp2 = FloorPlan::updateOrCreate(
                ['company_id' => $lannacom->id, 'name' => 'Lannacom HQ – Floor 2'],
                [
                    'location_id'  => $location?->id,
                    'building'     => 'HQ Building',
                    'floor'        => '2',
                    'zone'         => 'IT Room',
                    'image_url'    => null,
                    'is_active'    => true,
                ]
            );

            // Place Lannacom lockers on floor 1
            $lockers = Locker::where('company_id', $lannacom->id)->get();

            $positions = [
                ['pos_x' => 25, 'pos_y' => 30, 'zone' => 'Zone A'],
                ['pos_x' => 70, 'pos_y' => 45, 'zone' => 'Zone B'],
            ];

            foreach ($lockers->take(2) as $idx => $locker) {
                if (! LockerLocation::where('locker_id', $locker->id)->exists()) {
                    $svc->placeLocker(
                        locker:      $locker,
                        floorPlanId: $fp1->id,
                        posX:        $positions[$idx]['pos_x'],
                        posY:        $positions[$idx]['pos_y'],
                        zone:        $positions[$idx]['zone'],
                        placedBy:    null,
                        reason:      'Demo seeder',
                    );
                }
            }
        }

        // ── Nexastone: 1 floor plan ────────────────────────────────────
        if ($nexastone) {
            $location = $nexastone->locations()->first();

            $fp3 = FloorPlan::updateOrCreate(
                ['company_id' => $nexastone->id, 'name' => 'Nexastone Head Office – G Floor'],
                [
                    'location_id' => $location?->id,
                    'building'    => 'Head Office',
                    'floor'       => 'G',
                    'zone'        => 'Reception',
                    'image_url'   => null,
                    'is_active'   => true,
                ]
            );

            $locker = Locker::where('company_id', $nexastone->id)->first();
            if ($locker && ! LockerLocation::where('locker_id', $locker->id)->exists()) {
                $svc->placeLocker(
                    locker:      $locker,
                    floorPlanId: $fp3->id,
                    posX:        50,
                    posY:        35,
                    zone:        'Main Lobby',
                    placedBy:    null,
                    reason:      'Demo seeder',
                );
            }
        }

        // ── Dynatix: 1 floor plan ──────────────────────────────────────
        if ($dynatix) {
            $location = $dynatix->locations()->first();

            $fp4 = FloorPlan::updateOrCreate(
                ['company_id' => $dynatix->id, 'name' => 'Dynatix AIA Center – Floor 5'],
                [
                    'location_id' => $location?->id,
                    'building'    => 'AIA Capital Center',
                    'floor'       => '5',
                    'zone'        => 'Data Center',
                    'image_url'   => null,
                    'is_active'   => true,
                ]
            );

            $locker = Locker::where('company_id', $dynatix->id)->first();
            if ($locker && ! LockerLocation::where('locker_id', $locker->id)->exists()) {
                $svc->placeLocker(
                    locker:      $locker,
                    floorPlanId: $fp4->id,
                    posX:        40,
                    posY:        60,
                    zone:        'Server Room',
                    placedBy:    null,
                    reason:      'Demo seeder',
                );
            }
        }

        // ── Seed initial connection status logs for all lockers ────────
        Locker::all()->each(function (Locker $locker) use ($svc) {
            if ($locker->connections()->count() === 0) {
                $computed = $locker->computeConnectionStatus();

                LockerConnection::create([
                    'company_id' => $locker->company_id,
                    'locker_id'  => $locker->id,
                    'old_status' => null,
                    'new_status' => $computed,
                    'source'     => LockerConnection::SOURCE_SYSTEM,
                    'reason'     => 'Initial seeder record.',
                ]);

                if ($locker->connection_status !== $computed) {
                    $locker->update(['connection_status' => $computed]);
                }
            }
        });

        $this->command->info('FloorPlanSeeder complete.');
    }
}
