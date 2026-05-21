<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('locker_id')
                ->constrained('lockers')
                ->noActionOnDelete();

            $table->foreignId('locker_box_id')
                ->nullable()
                ->constrained('locker_boxes')
                ->noActionOnDelete();

            $table->string('event_type');

            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index('company_id');
            $table->index('locker_id');
            $table->index('locker_box_id');
            $table->index('event_type');
            $table->index(['company_id', 'event_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_events');
    }
};
