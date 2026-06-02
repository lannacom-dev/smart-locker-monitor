<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable audit trail for connection status transitions
        Schema::create('locker_connections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->noActionOnDelete();

            $table->foreignId('locker_id')
                ->constrained('lockers')
                ->noActionOnDelete();

            $table->string('old_status', 20)->nullable();  // null = first-ever log
            $table->string('new_status', 20);              // online | warning | offline
            $table->string('source', 30)->default('system'); // heartbeat | command | api | manual
            $table->string('reason', 255)->nullable();

            // Audit log: no updated_at
            $table->timestamp('created_at')->nullable();

            $table->index('locker_id');
            $table->index('company_id');
            $table->index(['locker_id', 'created_at']);
        });

        // Audit trail for locker position changes on floor plans
        Schema::create('locker_location_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->noActionOnDelete();

            $table->unsignedBigInteger('locker_id');   // no cascade — keep history
            $table->unsignedBigInteger('floor_plan_id')->nullable();
            $table->unsignedBigInteger('old_floor_plan_id')->nullable();

            $table->decimal('old_pos_x', 6, 3)->nullable();
            $table->decimal('old_pos_y', 6, 3)->nullable();
            $table->decimal('new_pos_x', 6, 3)->nullable();
            $table->decimal('new_pos_y', 6, 3)->nullable();

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('reason', 255)->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index('locker_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_location_logs');
        Schema::dropIfExists('locker_connections');
    }
};
