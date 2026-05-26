<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_checks', function (Blueprint $table) {
            $table->id();

            // null = global check (e.g. API health); non-null = per-company check
            $table->unsignedBigInteger('company_id')->nullable();

            // device_health | connection_health | api_health
            $table->string('check_type', 30);

            // healthy | warning | critical
            $table->string('status', 20);

            // Numeric score 0-100; higher = healthier
            $table->integer('score')->default(100);

            // Detailed breakdown (fault_rate, online_rate, error message, etc.)
            $table->json('details')->nullable();

            $table->timestamp('checked_at');
            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────
            $table->index(['company_id', 'check_type', 'checked_at'],
                          'shc_company_type_time_idx');
            $table->index('check_type');
            $table->index('checked_at');

            // ── FK — no cascade (keep history even if company deleted)
            $table->foreign('company_id')
                ->references('id')->on('companies')
                ->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_checks');
    }
};
