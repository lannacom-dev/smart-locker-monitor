<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();

            // company_id = null means system-wide type; non-null = tenant custom type
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->foreign('company_id')
                  ->references('id')->on('companies')
                  ->noActionOnDelete();

            $table->string('name', 100);
            $table->string('slug', 50)->unique();        // employee, visitor, delivery, …
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // system types cannot be deleted
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('company_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_types');
    }
};
