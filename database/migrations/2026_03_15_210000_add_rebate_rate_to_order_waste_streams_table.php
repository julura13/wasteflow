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
            $table->decimal('rebate_rate', 10, 2)->nullable()->after('nett_weight')
                ->comment('Rebate rate (R/kg) at time of weight capture/finalization for historical accuracy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_waste_streams', function (Blueprint $table) {
            $table->dropColumn('rebate_rate');
        });
    }
};
