<?php

namespace Database\Seeders;

use App\Models\Locker;
use App\Models\LockerBox;
use App\Models\LockerEvent;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic usage events (open / unlock) spread across the last 30 days
 * so the Usage Dashboard charts have something interesting to display.
 *
 * Safe to re-run — only adds events, never modifies existing ones.
 */
class UsageEventSeeder extends Seeder
{
    public function run(): void
    {
        $lockers = Locker::with('boxes')->get();

        if ($lockers->isEmpty()) {
            $this->command->warn('No lockers found. Run SmartLockerDemoSeeder first.');
            return;
        }

        $totalAdded = 0;

        foreach ($lockers as $locker) {
            $boxes = $locker->boxes->where('status', '!=', 'disabled');

            if ($boxes->isEmpty()) {
                continue;
            }

            // Generate events for the last 30 days
            for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
                $date = now()->subDays($daysAgo)->startOfDay();

                // Weekend = fewer events, weekday = more
                $isWeekend   = $date->isWeekend();
                $dailyEvents = $isWeekend
                    ? rand(1, 4)
                    : rand(3, 12);

                for ($i = 0; $i < $dailyEvents; $i++) {
                    $box  = $boxes->random();
                    $hour = rand(8, 19);   // business hours 08:00 – 19:59
                    $min  = rand(0, 59);
                    $sec  = rand(0, 59);

                    $timestamp = $date->copy()->setTime($hour, $min, $sec);

                    // Alternate between open and unlock events
                    $type = ($i % 2 === 0) ? LockerEvent::TYPE_OPEN : LockerEvent::TYPE_UNLOCK;

                    LockerEvent::create([
                        'company_id'    => $locker->company_id,
                        'locker_id'     => $locker->id,
                        'locker_box_id' => $box->id,
                        'event_type'    => $type,
                        'payload'       => [
                            'box_number' => $box->box_number,
                            'source'     => 'usage_seeder',
                        ],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                    $totalAdded++;
                }
            }
        }

        $this->command->info("UsageEventSeeder: added {$totalAdded} usage events across 30 days.");
    }
}
