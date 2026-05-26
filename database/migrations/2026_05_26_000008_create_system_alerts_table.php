<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_alerts', function (Blueprint $table) {
            $table->id();

            // null = global/system-wide alert
            $table->unsignedBigInteger('company_id')->nullable();

            // locker_offline | high_fault_rate | connection_degraded | api_unreachable
            $table->string('alert_type', 50);

            // info | warning | critical
            $table->string('severity', 20);

            $table->string('title', 200);
            $table->text('message');

            // Contextual data: locker_id, locker_name, metric values, etc.
            $table->json('context')->nullable();

            // Dedup key — prevent duplicate open alerts for the same issue
            // Format: {alert_type}:{entity_key}
            $table->string('fingerprint', 120);

            // open | acknowledged | resolved
            $table->string('status', 20)->default('open');

            // ── Acknowledge audit ──────────────────────────────────
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledge_note', 500)->nullable();

            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────
            $table->index(['company_id', 'status', 'severity'],    'sa_company_status_sev_idx');
            $table->index(['fingerprint', 'status'],               'sa_fingerprint_status_idx');
            $table->index(['status', 'severity', 'created_at'],    'sa_status_sev_time_idx');

            // ── FKs — no cascade (keep audit history)
            $table->foreign('company_id')
                ->references('id')->on('companies')
                ->noActionOnDelete();

            $table->foreign('acknowledged_by')
                ->references('id')->on('users')
                ->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_alerts');
    }
};
