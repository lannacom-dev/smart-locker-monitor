<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable audit log for every change on a corrective maintenance record.
 *
 * action values:
 *   created              — record first created
 *   status_changed       — status transition
 *   technician_assigned  — technician set / changed
 *   root_cause_updated   — root_cause field saved
 *   solution_updated     — solution field saved
 *   completed            — marked completed
 *   cancelled            — marked cancelled
 *   reopened             — re-opened after completed/cancelled
 *   field_updated        — generic field change (field_name tells which)
 *   cost_updated         — cost_estimate or cost_actual changed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corrective_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_id');

            // Who performed the action
            $table->unsignedBigInteger('changed_by');

            // Action classification
            $table->string('action',     50);
            $table->string('field_name', 100)->nullable();  // for 'field_updated' entries

            // Before / after
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            $table->text('note')->nullable();

            // Immutable — no updated_at
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes ───────────────────────────────────────────
            $table->index(['maintenance_id', 'created_at'], 'cml_maintenance_time_idx');
            $table->index(['maintenance_id', 'action'],     'cml_maintenance_action_idx');
            $table->index('changed_by',                     'cml_changed_by_idx');

            // ── FKs ───────────────────────────────────────────────
            $table->foreign('maintenance_id')
                ->references('id')->on('corrective_maintenances')->cascadeOnDelete();

            $table->foreign('changed_by')
                ->references('id')->on('users')->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrective_maintenance_logs');
    }
};
