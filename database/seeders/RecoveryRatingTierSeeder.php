<?php

namespace Database\Seeders;

use App\Models\RecoveryRatingTier;
use Illuminate\Database\Seeder;

class RecoveryRatingTierSeeder extends Seeder
{
    /**
     * Seed the default Resource Recovery Rating tiers. Thresholds are
     * editable afterwards via Settings > Resource Recovery Rating.
     */
    public function run(): void
    {
        $tiers = [
            ['name' => 'Platinum', 'slug' => 'platinum', 'min_percentage' => 90, 'color' => '#7C7C8A', 'sort_order' => 6],
            ['name' => 'Gold', 'slug' => 'gold', 'min_percentage' => 80, 'color' => '#D4AF37', 'sort_order' => 5],
            ['name' => 'Silver', 'slug' => 'silver', 'min_percentage' => 70, 'color' => '#A8A9AD', 'sort_order' => 4],
            ['name' => 'Bronze', 'slug' => 'bronze', 'min_percentage' => 60, 'color' => '#CD7F32', 'sort_order' => 3],
            ['name' => 'Developing', 'slug' => 'developing', 'min_percentage' => 50, 'color' => '#2D9CDB', 'sort_order' => 2],
            ['name' => 'Improvement Required', 'slug' => 'improvement-required', 'min_percentage' => 0, 'color' => '#E0563B', 'sort_order' => 1],
        ];

        foreach ($tiers as $tier) {
            RecoveryRatingTier::firstOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
