<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('site_id')->nullable()->constrained('sites');
            $table->foreignId('service_provider_id')->constrained('service_providers');
            $table->foreignId('created_by')->constrained('users');
            $table->string('order_type'); // waste | recycling
            $table->json('days_of_week'); // ['monday','tuesday',...]
            $table->json('quantity_lines'); // same shape as orders.quantity_lines
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_orders');
    }
};
