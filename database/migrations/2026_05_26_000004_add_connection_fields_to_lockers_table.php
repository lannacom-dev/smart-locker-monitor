<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            // Cached connection status (derived from last_seen_at; updated by heartbeat & command)
            $table->string('connection_status', 20)->default('offline')->after('status');

            // Seconds between expected heartbeats (device-configurable)
            $table->unsignedSmallInteger('heartbeat_interval')->default(60)->after('connection_status');

            // Seconds of silence before marking connection as OFFLINE
            $table->unsignedSmallInteger('offline_after')->default(300)->after('heartbeat_interval');

            $table->index('connection_status');
        });
    }

    public function down(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->dropIndex(['connection_status']);
            $table->dropColumn(['connection_status', 'heartbeat_interval', 'offline_after']);
        });
    }
};
