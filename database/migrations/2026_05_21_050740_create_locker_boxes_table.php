<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_boxes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('locker_id')
                ->constrained('lockers')
                ->noActionOnDelete();

            $table->integer('box_number');

            $table->enum('status', [
                'available',
                'occupied',
                'open',
                'error',
                'disabled',
            ])->default('available');

            $table->timestamp('last_opened_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['locker_id', 'box_number']);

            $table->index('company_id');
            $table->index('locker_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_boxes');
    }
};
