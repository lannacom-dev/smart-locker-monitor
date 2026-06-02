<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50)->default('smartlocker');
            $table->string('status', 20);
            $table->unsignedInteger('units_fetched')->default(0);
            $table->unsignedInteger('lockers_updated')->default(0);
            $table->unsignedInteger('lockers_skipped')->default(0);
            $table->unsignedInteger('lockers_not_mapped')->default(0);
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['source', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_runs');
    }
};
