<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();

            // Tenant scoping
            $table->unsignedBigInteger('company_id');

            // Optional locker this issue refers to
            $table->unsignedBigInteger('locker_id')->nullable();

            // People
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('assigned_to')->nullable();

            // Content
            $table->string('title', 255);
            $table->text('description');

            // Classification
            // category: hardware | software | network | power | other
            $table->string('category', 30)->default('other');

            // severity: low | medium | high | critical
            $table->string('severity', 20)->default('medium');

            // status: open | in_progress | resolved | closed
            $table->string('status', 20)->default('open');

            // Timing
            $table->date('due_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────
            $table->index(['company_id', 'status', 'severity'],  'issues_company_status_sev_idx');
            $table->index(['assigned_to', 'status'],             'issues_assignee_status_idx');
            $table->index(['locker_id', 'status'],               'issues_locker_status_idx');
            $table->index('status');
            $table->index('severity');
            $table->index('created_at');

            // ── FKs — noAction to avoid multiple cascade paths ────
            $table->foreign('company_id')
                ->references('id')->on('companies')->noActionOnDelete();

            $table->foreign('locker_id')
                ->references('id')->on('lockers')->noActionOnDelete();

            $table->foreign('reported_by')
                ->references('id')->on('users')->noActionOnDelete();

            $table->foreign('assigned_to')
                ->references('id')->on('users')->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
