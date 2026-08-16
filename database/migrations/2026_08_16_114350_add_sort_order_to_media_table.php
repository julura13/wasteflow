<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('collection');
        });

        // Backfill sheq_compliance documents with a sequential sort_order matching their
        // current (created_at) display order, so existing files don't all collapse to the
        // same/no order once SheqComplianceController starts ordering by sort_order.
        DB::table('media')
            ->where('collection', 'sheq_compliance')
            ->whereNull('mediable_type')
            ->orderBy('created_at')
            ->pluck('id')
            ->each(function (int $id, int $index): void {
                DB::table('media')->where('id', $id)->update(['sort_order' => $index + 1]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
