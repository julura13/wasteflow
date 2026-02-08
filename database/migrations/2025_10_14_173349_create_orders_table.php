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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->integer('company_id');
            $table->integer('branch_id')->nullable();
            $table->integer('site_id')->nullable();
            $table->integer('created_by');
            $table->enum('order_type', ['waste', 'recycling']);
            $table->enum('status', ['pending', 'scheduled', 'collected', 'sorted', 'finalized'])->default('pending');
            $table->date('requested_collection_date');
            $table->date('actual_collection_date')->nullable();
            $table->string('service_provider')->nullable();
            $table->string('slip_number')->nullable();
            $table->integer('estimated_quantity')->nullable(); // Number of bins/containers
            $table->integer('actual_quantity')->nullable(); // Actual collected quantity
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
