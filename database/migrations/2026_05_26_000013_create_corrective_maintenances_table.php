<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corrective maintenance records.
 *
 * status values  : created | in_progress | completed | cancelled
 * priority values: low | medium | high | urgent
 *
 * A corrective maintenance may be:
 *   - Linked to an issue  (issue_id, nullable)
 *   - Always linked to a locker
 *   - Scoped to a company
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corrective_maintenances', function (Blueprint $table) {
            $table->id();

            // Tenant + asset scoping
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('locker_id');
            $table->unsignedBigInteger('issue_id')->nullable();   // optional link

            // People
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('technician_id')->nullable();

            // Content
            $table->string('title', 255);
            $table->text('description');
            $table->text('root_cause')->nullable();
            $table->text('solution')->nullable();
            $table->text('notes')->nullable();

            // Classification
            // status  : created | in_progress | completed | cancelled
            $table->string('status',   20)->default('created');
            // priority: low | medium | high | urgent
            $table->string('priority', 20)->default('medium');

            // Scheduling + timing
            $table->date('scheduled_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            // Cost tracking
            $table->decimal('cost_estimate', 10, 2)->nullable();
            $table->decimal('cost_actual',   10, 2)->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────
            $table->index(['company_id', 'status', 'priority'],  'cm_company_status_pri_idx');
            $table->index(['locker_id',  'status'],              'cm_locker_status_idx');
            $table->index(['technician_id', 'status'],           'cm_tech_status_idx');
            $table->index('issue_id');
            $table->index('status');
            $table->index('created_at');

            // ── FKs — all noAction to avoid multiple cascade paths ─
            $table->foreign('company_id')
                ->references('id')->on('companies')->noActionOnDelete();

            $table->foreign('locker_id')
                ->references('id')->on('lockers')->noActionOnDelete();

            $table->foreign('issue_id')
                ->references('id')->on('issues')->noActionOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')->noActionOnDelete();

            $table->foreign('technician_id')
                ->references('id')->on('users')->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrective_maintenances');
    }
};
