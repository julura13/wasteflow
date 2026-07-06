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
            [
                'version' => '0.4.0',
                'type' => 'improvement',
                'title' => 'Notification detail modal',
                'description' => 'Clicking a notification now opens a modal with the full message instead of truncating the text. Use the × button to dismiss directly, or "Mark as read" from inside the modal.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'improvement',
                'title' => 'Material usage shows location type',
                'description' => 'The usage history on material pages now shows a small label (Site, Branch, or Company) beneath the location name, making it clear what level the order is linked to.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'improvement',
                'title' => 'Material usage history now links to orders',
                'description' => 'Each order in the material usage history is now clickable and navigates directly to the order detail page.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'improvement',
                'title' => 'Updated logo on collection summary PDF',
                'description' => 'The Summary of Orders for Collection PDF now displays the full WasteFlow logo instead of the icon-only version.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'feature',
                'title' => 'User impersonation for admins',
                'description' => 'Admins can now impersonate any non-admin user from the Edit User page to test their access and view the app as them. A yellow banner is shown while impersonating with a one-click stop button.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'bugfix',
                'title' => 'Resource Intelligence: Total Waste Processed corrected',
                'description' => 'Total Waste Processed now correctly shows Total Diverted + Total Disposal, matching the classification totals shown in the donut charts.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'improvement',
                'title' => 'Updated logo on collection order form PDF',
                'description' => 'The Waste Collection Order Form PDF now displays the full WasteFlow logo instead of the icon-only version.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.0',
                'type' => 'bugfix',
                'title' => 'Resource Intelligence: Wood and FOG Recovered now included',
                'description' => 'The grade table\'s "Organics Recovered" row has been replaced with "Total Recovery", which correctly includes all recovery materials (Organics, Wood, FOG Recovered, etc.). The Diversion Rate percentage is also now consistent with the donut chart.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'bugfix',
                'title' => 'Circularity (Reuse) now shows correctly in reports',
                'description' => 'Fixed a bug where Circularity (Reuse) weight was being counted as Material Recovery. The report table now shows separate rows for Organics Recovery, Material Recovery, Circularity (Reuse), and Total Recycling under a Waste Diverted section, with a TOTAL WASTE MANAGED grand total.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'improvement',
                'title' => 'Unified calculation engine',
                'description' => 'All environmental impact metrics (carbon, energy, water, trees, landfill space) now flow through a single shared calculation engine. Duplicate category-mapping loops have been removed, plastic grades are split correctly (PP/HD, PS, LDPE) with a fallback for unrecognised grades, and an organics double-count bug has been fixed. Dashboard and report carbon figures now match.',
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
