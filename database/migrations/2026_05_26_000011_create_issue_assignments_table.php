<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable audit log for every significant change on an issue.
 *
 * type values:
 *   created        — issue was first created
 *   assigned       — issue assigned to a user
 *   unassigned     — assignment removed
 *   status_changed — status transition (old_value → new_value)
 *   resolved       — marked resolved
 *   closed         — marked closed
 *   reopened       — reopened after resolved/closed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('issue_id');

            // Action type (see phpdoc above)
            $table->string('type', 30);

            // Who performed the action
            $table->unsignedBigInteger('performed_by');

            // For assignment entries: who was assigned (nullable for unassign)
            $table->unsignedBigInteger('assigned_to')->nullable();

            // Before / after values for status changes and assignments
            $table->string('old_value', 100)->nullable();   // e.g. 'open', 'John Smith'
            $table->string('new_value', 100)->nullable();   // e.g. 'in_progress', 'Jane Doe'

            $table->text('note')->nullable();               // optional free-text context

            // Immutable — no updated_at
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes ───────────────────────────────────────────
            $table->index(['issue_id', 'created_at'],  'ia_issue_time_idx');
            $table->index('performed_by');
            $table->index(['issue_id', 'type'],        'ia_issue_type_idx');

            // ── FKs ───────────────────────────────────────────────
            // Cascade: deleting an issue removes its audit trail
            $table->foreign('issue_id')
                ->references('id')->on('issues')->cascadeOnDelete();

            // Keep references even if users are deleted
            $table->foreign('performed_by')
                ->references('id')->on('users')->noActionOnDelete();

            $table->foreign('assigned_to')
                ->references('id')->on('users')->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_assignments');
    }
};
