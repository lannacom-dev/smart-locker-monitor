<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->noActionOnDelete();

            $table->foreignId('locker_id')
                ->unique()                           // one locker → one position
                ->constrained('lockers')
                ->noActionOnDelete();

            $table->foreignId('floor_plan_id')
                ->constrained('floor_plans')
                ->noActionOnDelete();

            // percentage position (0.000 – 100.000) relative to floor plan image
            $table->decimal('pos_x', 6, 3)->default(50.000);
            $table->decimal('pos_y', 6, 3)->default(50.000);

            $table->string('zone', 100)->nullable();   // sub-zone label on this floor plan
            $table->text('note')->nullable();

            $table->foreignId('placed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('floor_plan_id');
            $table->index(['floor_plan_id', 'zone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_locations');
    }
};
