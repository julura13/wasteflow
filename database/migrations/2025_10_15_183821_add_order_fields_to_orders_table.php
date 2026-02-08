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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('service_provider_id')->nullable()->after('site_id')->constrained()->onDelete('set null');
            $table->string('waste_type')->nullable()->after('order_type');
            $table->enum('quantity_type', ['rel_skip', 'wheelie_bins', 'skips_30m2'])->nullable()->after('waste_type');
            $table->integer('quantity')->nullable()->after('quantity_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['service_provider_id']);
            $table->dropColumn(['service_provider_id', 'waste_type', 'quantity_type', 'quantity']);
        });
    }
};