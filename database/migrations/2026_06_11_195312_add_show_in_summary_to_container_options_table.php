<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('container_options', function (Blueprint $table) {
            $table->boolean('show_in_summary')->default(false)->after('default_weight');
        });
    }

    public function down(): void
    {
        Schema::table('container_options', function (Blueprint $table) {
            $table->dropColumn('show_in_summary');
        });
    }
};
