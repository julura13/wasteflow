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
            [
                'version' => '0.4.1',
                'type' => 'feature',
                'title' => 'Document Library',
                'description' => 'Admins can now upload shared documents (title, description, and file) from the new Documents section in the sidebar. Everyone can view and download them, and a badge marks documents you have not opened yet.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'improvement',
                'title' => 'Roles now show descriptions',
                'description' => 'Each role in Roles & Permissions now has a plain-English description of what it grants. Cleaned up a few duplicate/unused permissions and made sure every permission is grouped into a section.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'improvement',
                'title' => 'Live search on Users page',
                'description' => 'Searching on the Users page now updates the list as you type instead of requiring a Filter click, and also matches against company name.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'bugfix',
                'title' => 'Roles table display fixed',
                'description' => 'Fixed role descriptions visually overlapping into the Permissions column on the Roles page, and trimmed the permission chips shown per role to 3 with a "+N more" indicator.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'improvement',
                'title' => 'Hide deleted recurring orders by default',
                'description' => 'The Recurring Orders list now hides deleted templates by default. Tick "Show deleted records" to bring them back into view, with a Restore action still available for each.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'feature',
                'title' => 'View documents inline',
                'description' => 'Added a "View" button on the Documents library to open files in a new tab for preview, alongside the existing Download option.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'feature',
                'title' => 'New Waste Stream Collection Report',
                'description' => 'Added a new standalone report, "Waste Stream Collection Report", grouping collections by waste stream and grade, with order number, collection date, slip number, and quantity per order and a weight subtotal per group. No pricing shown. Available from the Reports index with its own filters and PDF download.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'feature',
                'title' => 'Circular economy rate, barrels of oil, and homes powered metrics',
                'description' => 'Added three new metrics to the dashboard and monthly report Environmental Impact section: Circular Economy Rate (reuse + recycling + organics recovery as a share of total waste managed), Barrels of Oil Saved, and Homes Powered (1 Month) — both derived from energy saved through recycling.',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'feature',
                'title' => 'Jan-Dec line graphs on the monthly report',
                'description' => 'The WasteFlow Resource Intelligence Report now includes two Jan-Dec line graphs: Monthly Material Recovery by Waste Grade (kg), and Waste Management Performance Trends (Total Waste Diverted, Waste to Landfill, Total Waste Managed).',
                'released_at' => now(),
            ],
            [
                'version' => '0.4.1',
                'type' => 'feature',
                'title' => 'New Management Report (all clients, one month)',
                'description' => 'Added a new cross-client Management Report showing total waste diverted % and container-type totals for every client in a single selected month, with CSV and PDF export. Available from the Reports index (requires the view-reports-all permission).',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.0',
                'type' => 'feature',
                'title' => 'Custom date-range reporting',
                'description' => 'The WasteFlow Resource Intelligence Report (both the on-screen view and the PDF download) can now cover a custom period instead of just one calendar month — for example January to June. Use the new "From" and "To" month/year filters; leave "To" the same as "From" to get a normal single-month report like before.',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.0',
                'type' => 'improvement',
                'title' => 'Resource Intelligence report: clearer metric names and a Recovery Rate figure',
                'description' => 'Renamed "Trees Saved" to "Trees Preserved" and "Landfill Space Saved" to "Landfill Airspace Saved" to match our standard reporting terminology, and added a dedicated Recovery Rate percentage next to Diversion Rate so every core sustainability indicator now has its own clearly labelled figure.',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.0',
                'type' => 'feature',
                'title' => 'Release notes page',
                'description' => 'Click the version number next to "WasteFlow" in the sidebar to open a full history of every release, grouped by version with a description of what changed and why — features, fixes, and improvements all in one place, visible to every user.',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.1',
                'type' => 'bugfix',
                'title' => 'Branded forgot password / reset password pages',
                'description' => 'The "Forgot your password?" and "Reset password" pages were still showing the plain default Laravel look instead of the WasteFlow branding used everywhere else in the portal (including the Login and Register pages). Both pages have been redesigned to match, with the same green branding, logo, and layout.',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.2',
                'type' => 'bugfix',
                'title' => 'Admins are now notified of new user registrations',
                'description' => 'New user sign-ups are created inactive and need an admin to approve them, but until now nothing told anyone a new registration was waiting — you had to remember to check the Users page. Admins now get a bell notification and an email as soon as someone registers, with a link straight to the Users page to review and approve them.',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.3',
                'type' => 'bugfix',
                'title' => 'Full audit and fix of unbranded pages',
                'description' => 'Checked every page in the portal for default, unbranded styling. Confirm Password and Verify Email were still using the plain default Laravel look — both now match the WasteFlow branding used everywhere else in the app (same layout as Login, Register, and the recently fixed Forgot/Reset Password pages).',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.4',
                'type' => 'improvement',
                'title' => 'Password reset emails now go out via Communicator',
                'description' => 'Password reset (and email verification) emails now send through our internal Communicator service instead of always going out over SMTP, matching how other system emails already work. This also lays the groundwork for a reusable "julura/laravel-communicator" package we can install in other projects going forward.',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.5',
                'type' => 'bugfix',
                'title' => 'Security hardening: document/slip access now correctly scoped per client',
                'description' => 'A full security review found that downloading, uploading, or deleting a collection document/slip did not check which client it belonged to. Fixed so this now follows the same per-client access rules already used everywhere else in the portal.',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.6',
                'type' => 'feature',
                'title' => 'Configurable Resource Recovery Rating thresholds',
                'description' => 'New Settings > Resource Recovery Rating page lets admins set the diversion-rate cutoffs for each rating tier (Platinum, Gold, Silver, Bronze, Developing, Improvement Required).',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.7',
                'type' => 'feature',
                'title' => 'Multi service-provider rebate support',
                'description' => 'Each load in an order can now specify its own service provider (defaulting to the order\'s provider) instead of every load sharing one provider. The Rebate Tracker report and PDF export now break out weight and rebate totals per provider.',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.8',
                'type' => 'feature',
                'title' => 'Client Monthly Certificates',
                'description' => 'A "Download Certificate" button on the Resource Intelligence report page generates a branded Certificate of Waste Diversion for the selected client and month, showing their landfill diversion percentage and reporting period.',
                'released_at' => now(),
            ],
            [
                'version' => '1.8.9',
                'type' => 'feature',
                'title' => 'Carbon Avoidance Intensity (CAI) metric',
                'description' => 'New indicator on the Resource Intelligence report: kg CO2e avoided per kg of waste managed, a volume-independent measure of how carbon-efficient a client\'s diversion program is.',
                'released_at' => now(),
            ],
            [
                'version' => '1.9.0',
                'type' => 'improvement',
                'title' => 'Updated Methodology & Data Sources report copy',
                'description' => 'The Resource Intelligence report\'s Methodology & Data Sources section now includes the client\'s updated legal/trademark language: Resource Intelligence™ and Resource Recovery Rating™ trademark symbols, an expanded methodology and indicators list, and new Reporting Statement, Limitations, Disclaimer and copyright sections.',
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
