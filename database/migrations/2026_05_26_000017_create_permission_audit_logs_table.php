<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('causer_id')->nullable();    // who performed the action
            $table->string('action', 60);                           // e.g. user.created, roles.synced
            $table->string('target_type', 20);                      // 'user' or 'role'
            $table->unsignedBigInteger('target_id');                // user.id or role.id
            $table->string('target_name', 255);                     // cached display name
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('note')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('causer_id')
                  ->references('id')->on('users')
                  ->noActionOnDelete();

            $table->index(['target_type', 'target_id']);
            $table->index(['causer_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_audit_logs');
    }
};
