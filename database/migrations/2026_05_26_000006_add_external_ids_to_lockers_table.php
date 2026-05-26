<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            // Lannacom API lockerID (the physical locker cabinet)
            $table->integer('external_locker_id')->nullable()->after('id');
            // Lannacom API lockerUnitID (individual box/unit inside the cabinet)
            $table->integer('external_unit_id')->nullable()->after('external_locker_id');

            $table->index(['external_locker_id', 'external_unit_id'], 'lockers_external_ids_index');
        });
    }

    public function down(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->dropIndex('lockers_external_ids_index');
            $table->dropColumn(['external_locker_id', 'external_unit_id']);
        });
    }
};
