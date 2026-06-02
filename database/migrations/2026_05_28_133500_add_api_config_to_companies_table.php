<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('api_base_url')->nullable()->after('contact_phone');
            $table->string('api_client_id')->nullable()->after('api_base_url');
            $table->string('api_client_secret')->nullable()->after('api_client_id');
            $table->unsignedSmallInteger('api_timeout')->default(10)->after('api_client_secret');
            $table->boolean('api_enabled')->default(false)->after('api_timeout');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'api_base_url',
                'api_client_id',
                'api_client_secret',
                'api_timeout',
                'api_enabled',
            ]);
        });
    }
};
