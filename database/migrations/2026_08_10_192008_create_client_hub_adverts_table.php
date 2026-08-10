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
        Schema::create('client_hub_adverts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('details')->nullable();
            $table->string('contact_email')->default('crm@wasteflow.example.com');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('disk');
            $table->string('path');
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_hub_adverts');
    }
};
