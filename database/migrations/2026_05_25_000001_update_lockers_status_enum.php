<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE lockers MODIFY COLUMN status ENUM('available','in_use','fault','offline','disabled') NOT NULL DEFAULT 'offline'");

        DB::table('lockers')->where('status', 'online')->update(['status' => 'available']);
        DB::table('lockers')->where('status', 'maintenance')->update(['status' => 'in_use']);
        DB::table('lockers')->where('status', 'error')->update(['status' => 'fault']);
    }

    public function down(): void
    {
        DB::table('lockers')->where('status', 'available')->update(['status' => 'online']);
        DB::table('lockers')->where('status', 'in_use')->update(['status' => 'maintenance']);
        DB::table('lockers')->where('status', 'fault')->update(['status' => 'error']);
        DB::table('lockers')->where('status', 'disabled')->update(['status' => 'offline']);

        DB::statement("ALTER TABLE lockers MODIFY COLUMN status ENUM('online','offline','maintenance','error') NOT NULL DEFAULT 'offline'");
    }
};
