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
        Schema::table('order_waste_streams', function (Blueprint $table) {
            $table->foreignId('service_provider_id')->nullable()->after('material_id')
                ->constrained()->nullOnDelete()
                ->comment('Provider that collected this specific load; falls back to the order\'s provider when null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_waste_streams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_provider_id');
        });
    }
};
