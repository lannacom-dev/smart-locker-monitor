<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('issue_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body');

            // Internal note (only visible to staff) vs public comment
            $table->boolean('is_internal')->default(false);

            // Immutable — comments are never edited
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes ───────────────────────────────────────────
            $table->index('issue_id');
            $table->index(['issue_id', 'created_at'], 'ic_issue_time_idx');

            // ── FKs ───────────────────────────────────────────────
            // Cascade: deleting an issue removes its comments
            $table->foreign('issue_id')
                ->references('id')->on('issues')->cascadeOnDelete();

            // Keep comment author reference even if user is deleted
            $table->foreign('user_id')
                ->references('id')->on('users')->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_comments');
    }
};
