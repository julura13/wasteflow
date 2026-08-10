<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Standalone media (not attached to an Order/etc., e.g. SHEQ Compliance documents)
     * needs mediable_type/mediable_id to be nullable, and a user-facing title distinct
     * from the raw uploaded filename. Uses raw SQL for the column-type change since this
     * project doesn't have doctrine/dbal (required for Schema::table()->change()).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE media MODIFY mediable_type VARCHAR(255) NULL');
        DB::statement('ALTER TABLE media MODIFY mediable_id BIGINT UNSIGNED NULL');

        Schema::table('media', function (Blueprint $table) {
            $table->string('title')->nullable()->after('mediable_id');
            $table->foreignId('uploaded_by')->nullable()->after('description')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn('title');
        });

        DB::statement('ALTER TABLE media MODIFY mediable_type VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE media MODIFY mediable_id BIGINT UNSIGNED NOT NULL');
    }
};
