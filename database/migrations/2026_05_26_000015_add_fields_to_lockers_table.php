<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('name');
            $table->string('zone', 100)->nullable()->after('location_id');
            $table->string('floor', 50)->nullable()->after('zone');
            // tenant_id: which company is currently assigned/using this locker
            $table->unsignedBigInteger('tenant_id')->nullable()->after('company_id');
            $table->foreign('tenant_id')
                  ->references('id')->on('companies')
                  ->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['code', 'zone', 'floor', 'tenant_id']);
        });
    }
};
