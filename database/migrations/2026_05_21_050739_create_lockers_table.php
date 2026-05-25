<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lockers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->noActionOnDelete();

            $table->string('name');
            $table->string('serial_number')->unique();

            $table->string('api_token')->unique();

            $table->string('ip_address')->nullable();

            $table->enum('status', [
                'available',
                'in_use',
                'fault',
                'offline',
                'disabled',
            ])->default('offline');

            $table->timestamp('last_seen_at')->nullable();

            $table->string('firmware_version')->nullable();

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('company_id');
            $table->index('location_id');
            $table->index('status');
            $table->index('last_seen_at');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lockers');
    }
};