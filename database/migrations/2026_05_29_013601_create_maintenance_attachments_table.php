<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_id');
            $table->unsignedBigInteger('uploaded_by');
            $table->string('phase', 20);          // before | during | after
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size')->nullable();  // bytes
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('maintenance_id')
                  ->references('id')->on('corrective_maintenances')
                  ->cascadeOnDelete();

            $table->foreign('uploaded_by')
                  ->references('id')->on('users')
                  ->noActionOnDelete();

            $table->index(['maintenance_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_attachments');
    }
};
