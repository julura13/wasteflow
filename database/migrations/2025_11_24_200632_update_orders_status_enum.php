<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing statuses to new values
        // Map 'collected' and 'sorted' to 'weight_required' as they would need weights
        DB::table('orders')
            ->whereIn('status', ['collected', 'sorted'])
            ->update(['status' => 'weight_required']);

        // Update the enum column using raw SQL
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'scheduled', 'weight_required', 'documents_required', 'finalized') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Map back to old statuses
        DB::table('orders')
            ->where('status', 'weight_required')
            ->update(['status' => 'collected']);

        DB::table('orders')
            ->where('status', 'documents_required')
            ->update(['status' => 'sorted']);

        // Restore the old enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'scheduled', 'collected', 'sorted', 'finalized') DEFAULT 'pending'");
    }
};
