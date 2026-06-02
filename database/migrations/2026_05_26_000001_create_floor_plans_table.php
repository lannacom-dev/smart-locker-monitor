<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floor_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('location_id')
                ->constrained('locations')
                ->noActionOnDelete();

            $table->string('name');
            $table->string('building', 100)->nullable();  // e.g. "Building A", "Tower 1"
            $table->string('floor', 50)->nullable();       // e.g. "1", "B1", "G", "RF"
            $table->string('zone', 100)->nullable();        // e.g. "Zone A", "Data Center"

            $table->string('image_path', 500)->nullable(); // stored file path
            $table->string('image_url', 500)->nullable();  // external URL (alternative)
            $table->unsignedSmallInteger('image_width')->nullable();
            $table->unsignedSmallInteger('image_height')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
            $table->index('location_id');
            $table->index(['company_id', 'building', 'floor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floor_plans');
    }
};
