<?php

namespace Database\Seeders;

use App\Models\ReleaseNote;
use Illuminate\Database\Seeder;

class ReleaseNotesSeeder extends Seeder
{
    public function run(): void
    {
        $notes = [
            [
                'version' => '0.4.0',
                'type' => 'feature',
                'title' => 'Global search (⌘K)',
                'description' => 'Search across orders, companies, users, branches, collection points, service providers, waste streams, grades and container types from anywhere using ⌘K or Ctrl+K.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'feature',
                'title' => 'Notification bell',
                'description' => 'Admin users now receive in-app notifications for new features, bug fixes, backup results, and recurring order run summaries.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'feature',
                'title' => 'Wood material added',
                'description' => 'Wood has been added as a collectable material with full ordering and reporting support.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'improvement',
                'title' => 'Dashboard container breakdown',
                'description' => 'The dashboard now shows a per-container breakdown for better operational visibility.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'improvement',
                'title' => "Recurring orders run tomorrow's orders",
                'description' => "Scheduled recurring orders now generate for tomorrow's date as intended.",
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'improvement',
                'title' => 'Circularity (Reuse) renamed',
                'description' => 'The waste stream previously called "Avoidance" has been renamed to "Circularity (Reuse)" to better reflect its purpose.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'bugfix',
                'title' => 'Report totals fixed (Circularity Reuse)',
                'description' => 'Fixed report diversion totals and Total Waste Processed being incorrect after the Avoidance → Circularity (Reuse) rename. Wood Pallets now correctly count toward diverted waste.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'bugfix',
                'title' => 'Material usage shows branch name',
                'description' => 'Orders without a specific collection point (site) now display the branch name instead of "Unknown Site" on the material usage history.',
                'released_at' => now(),
            ],
        ];

        foreach ($notes as $note) {
            ReleaseNote::firstOrCreate(
                ['version' => $note['version'], 'title' => $note['title']],
                $note
            );
        }
    }
}
