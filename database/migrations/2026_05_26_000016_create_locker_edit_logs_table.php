<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_edit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('locker_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('field_name', 100);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('locker_id')
                  ->references('id')->on('lockers')
                  ->cascadeOnDelete();
            $table->foreign('changed_by')
                  ->references('id')->on('users')
                  ->noActionOnDelete();

            $table->index(['locker_id', 'created_at']);
            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_edit_logs');
    }
};
