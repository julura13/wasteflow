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
        Schema::table('media', function (Blueprint $table) {
            $table->string('local_disk')->nullable()->after('disk');
            $table->string('local_path')->nullable()->after('path');
            $table->timestamp('local_cached_at')->nullable()->after('local_path');
            $table->timestamp('local_deleted_at')->nullable()->after('local_cached_at');

            $table->index(['local_deleted_at', 'local_cached_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['local_deleted_at', 'local_cached_at']);
            $table->dropColumn([
                'local_disk',
                'local_path',
                'local_cached_at',
                'local_deleted_at',
            ]);
        });
    }
};
