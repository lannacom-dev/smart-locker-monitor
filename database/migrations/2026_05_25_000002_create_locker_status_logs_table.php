<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locker_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->foreignId('locker_id')->constrained('lockers')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('company_id');
            $table->index('locker_id');
            $table->index('changed_by');
            $table->index('created_at');
            $table->index(['company_id', 'locker_id']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locker_status_logs');
    }
};
