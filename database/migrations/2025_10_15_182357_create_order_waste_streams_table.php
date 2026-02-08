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
        Schema::create('order_waste_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('gross_weight', 10, 3);
            $table->decimal('tare_weight', 10, 3)->default(0);
            $table->decimal('nett_weight', 10, 3);
            $table->integer('quantity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_waste_streams');
    }
};

