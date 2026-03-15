<?php

namespace App\Console\Commands;

use App\Services\ClientMonthlySummaryService;
use Illuminate\Console\Command;

class ReconcileGradeSummaryMonths extends Command
{
    protected $signature = 'grade-summary:reconcile-months
                            {--dry-run : Show what would be done without changing data}';

    protected $description = 'Rebuild client monthly summaries from order waste streams so weights appear in the correct (actual) collection month. Use this to fix wrong totals.';

    public function handle(ClientMonthlySummaryService $service): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run – no data will be changed.');
            $this->line('Would rebuild client monthly material summaries from order waste streams (actual_collection_date or requested_collection_date per order).');

            return self::SUCCESS;
        }

        $this->info('Rebuilding client monthly material summaries from order waste streams...');
        $count = $service->rebuildFromOrderWasteStreams(null);
        $this->info("Done. Created/updated {$count} summary rows. Totals should now match order data.");

        return self::SUCCESS;
    }
}
