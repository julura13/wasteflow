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
            // Make branch_id nullable for small companies without branches
            $table->dropForeign(['branch_id']);
            $table->foreignId('branch_id')->nullable()->change();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            
            // Add direct company_id for small companies
            $table->foreignId('company_id')->nullable()->after('branch_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            
            $table->dropForeign(['branch_id']);
            $table->foreignId('branch_id')->nullable(false)->change();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }
};