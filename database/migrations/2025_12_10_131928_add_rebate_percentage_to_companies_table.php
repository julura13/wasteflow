<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('rebate_percentage', 5, 2)->nullable()->after('registration_number')->comment('Company-specific rebate percentage (overrides material client_rebate_share if set)');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('rebate_percentage');
        });
    }
};
