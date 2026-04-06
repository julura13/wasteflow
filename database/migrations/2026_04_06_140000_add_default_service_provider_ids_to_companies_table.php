<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('default_waste_service_provider_id')
                ->nullable()
                ->after('rebate_percentage')
                ->constrained('service_providers')
                ->nullOnDelete();
            $table->foreignId('default_recycling_service_provider_id')
                ->nullable()
                ->after('default_waste_service_provider_id')
                ->constrained('service_providers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_recycling_service_provider_id');
            $table->dropConstrainedForeignId('default_waste_service_provider_id');
        });
    }
};
