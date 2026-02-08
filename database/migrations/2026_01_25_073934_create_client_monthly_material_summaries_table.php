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
        Schema::create('client_monthly_material_summaries', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('branch_id')->nullable();
            $table->integer('site_id')->nullable();
            $table->integer('year');
            $table->integer('month'); // 1-12
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('waste_stream_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_weight', 10, 3)->default(0);
            $table->integer('order_count')->default(0);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            // Unique constraint: one record per company/branch/site/year/month/material or waste_stream combination
            $table->unique([
                'company_id',
                'branch_id',
                'site_id',
                'year',
                'month',
                'material_id',
                'waste_stream_id',
            ], 'client_monthly_summary_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_monthly_material_summaries');
    }
};
