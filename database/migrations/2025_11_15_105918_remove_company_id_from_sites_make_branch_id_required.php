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
        Schema::table('sites', function (Blueprint $table) {
            // Remove company_id column (collection points must always belong to a branch)
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            
            // Make branch_id required (not nullable)
            $table->dropForeign(['branch_id']);
            $table->foreignId('branch_id')->nullable(false)->change();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            // Restore branch_id as nullable
            $table->dropForeign(['branch_id']);
            $table->foreignId('branch_id')->nullable()->change();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            
            // Restore company_id column
            $table->foreignId('company_id')->nullable()->after('branch_id')->constrained()->onDelete('cascade');
        });
    }
};
