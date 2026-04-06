<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_index_exports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('format', 8);
            $table->string('status', 32)->default('pending');
            $table->string('disk', 32)->default('local');
            $table->string('path')->nullable();
            $table->string('filename');
            $table->text('error_message')->nullable();
            $table->json('filters');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_index_exports');
    }
};
