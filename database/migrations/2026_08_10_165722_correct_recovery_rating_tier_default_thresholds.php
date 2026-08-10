<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The Resource Recovery Rating thresholds were seeded with placeholder defaults
     * before the client's actual spec was available. Corrects Gold/Silver/Bronze/
     * Developing to match that spec. Only updates rows still holding the original
     * placeholder value, so any threshold an admin has since edited via
     * Settings > Resource Recovery Rating is left untouched. Platinum (90) and
     * Improvement Required (0) already matched the spec and are not changed.
     */
    public function up(): void
    {
        $corrections = [
            'gold' => ['from' => 75, 'to' => 80],
            'silver' => ['from' => 60, 'to' => 70],
            'bronze' => ['from' => 40, 'to' => 60],
            'developing' => ['from' => 20, 'to' => 50],
        ];

        foreach ($corrections as $slug => $range) {
            DB::table('recovery_rating_tiers')
                ->where('slug', $slug)
                ->where('min_percentage', $range['from'])
                ->update(['min_percentage' => $range['to']]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $corrections = [
            'gold' => ['from' => 75, 'to' => 80],
            'silver' => ['from' => 60, 'to' => 70],
            'bronze' => ['from' => 40, 'to' => 60],
            'developing' => ['from' => 20, 'to' => 50],
        ];

        foreach ($corrections as $slug => $range) {
            DB::table('recovery_rating_tiers')
                ->where('slug', $slug)
                ->where('min_percentage', $range['to'])
                ->update(['min_percentage' => $range['from']]);
        }
    }
};
