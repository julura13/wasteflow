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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable'); // Creates mediable_type and mediable_id
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('disk')->default('local');
            $table->string('path'); // Full path on the disk
            $table->unsignedBigInteger('file_size'); // Size in bytes
            $table->string('collection')->default('default'); // For grouping files (e.g., 'supporting_documents', 'images')
            $table->text('description')->nullable();
            $table->timestamps();
            
            // morphs() already creates an index on mediable_type and mediable_id
            $table->index('collection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
