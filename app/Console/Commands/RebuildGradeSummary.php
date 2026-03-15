<?php

namespace App\Console\Commands;

use App\Services\ClientMonthlySummaryService;
use Illuminate\Console\Command;

class RebuildGradeSummary extends Command
{
    protected $signature = 'grade-summary:rebuild
                            {--year= : Rebuild only this year (e.g. 2026)}';

    protected $description = 'Rebuild client monthly material summaries from order waste streams (fixes wrong totals)';

    public function handle(ClientMonthlySummaryService $service): int
    {
        $year = $this->option('year') ? (int) $this->option('year') : null;

        if ($year !== null) {
            $this->info("Rebuilding client monthly summaries for year {$year}...");
        } else {
            $this->info('Rebuilding all client monthly material summaries from order waste streams...');
        }

        $count = $service->rebuildFromOrderWasteStreams($year);

        $this->info("Done. Created/updated {$count} summary rows.");

        return self::SUCCESS;
    }
}
