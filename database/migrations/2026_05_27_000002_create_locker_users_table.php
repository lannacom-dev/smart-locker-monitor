<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')
                  ->references('id')->on('companies')
                  ->noActionOnDelete();

            $table->unsignedBigInteger('user_type_id');
            $table->foreign('user_type_id')
                  ->references('id')->on('user_types')
                  ->noActionOnDelete();

            // Tracks which admin created / last updated
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->noActionOnDelete();

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')
                  ->references('id')->on('users')
                  ->noActionOnDelete();

            // ── Identity ──────────────────────────────────────────
            $table->string('full_name', 255);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();

            // ── Type-specific fields ──────────────────────────────
            $table->string('employee_id', 100)->nullable();   // EMPLOYEE
            $table->string('organization', 255)->nullable();  // DELIVERY / EXTERNAL_USER

            // ── Access control ────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->date('access_start_date')->nullable();
            $table->date('access_end_date')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────
            $table->index('company_id');
            $table->index('user_type_id');
            $table->index('is_active');
            $table->index(['company_id', 'is_active'],      'lu_company_active_idx');
            $table->index(['company_id', 'user_type_id'],   'lu_company_type_idx');
            $table->index(['email'],                         'lu_email_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_users');
    }
};
