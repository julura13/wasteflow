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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('weight_required')->default('Yes');
            $table->boolean('rebate_offered')->default(false);
            $table->decimal('rebate_rate', 10, 2)->nullable();
            $table->decimal('client_rebate_share', 5, 2)->nullable();
            $table->boolean('backing_document')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique([
                'waste_stream_id',
                'grade_id',
                'classification_id',
                'facility_id',
                'service_provider_id',
            ], 'materials_unique_combination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};