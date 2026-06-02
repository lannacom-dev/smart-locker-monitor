<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated audit table for every status transition on an issue.
 * More granular than issue_assignments — stores the full state-machine context.
 *
 * status values: open | in_progress | pending | resolved | closed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('issue_id');

            // Actor
            $table->unsignedBigInteger('changed_by');

            // Transition
            $table->string('from_status', 20);
            $table->string('to_status',   20);

            // Context
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();   // IP, user-agent, source (api|web|command)

            // Immutable — no updated_at
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes ───────────────────────────────────────────
            $table->index(['issue_id', 'created_at'],  'ish_issue_time_idx');
            $table->index(['issue_id', 'to_status'],   'ish_issue_status_idx');
            $table->index('changed_by',                'ish_changed_by_idx');

            // ── FKs ───────────────────────────────────────────────
            $table->foreign('issue_id')
                ->references('id')->on('issues')->cascadeOnDelete();

            $table->foreign('changed_by')
                ->references('id')->on('users')->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_status_histories');
    }
};
