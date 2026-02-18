<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ClientMonthlySummaryService;
use Illuminate\Console\Command;

class ReconcileGradeSummaryMonths extends Command
{
    protected $signature = 'grade-summary:reconcile-months
                            {--dry-run : Show what would be updated without changing data}';

    protected $description = 'Move monthly summary weights from requested to actual collection month for finalized orders (fixes Grade Summary showing wrong month)';

    public function handle(ClientMonthlySummaryService $service): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run – no data will be changed.');
        }

        $orders = Order::query()
            ->where('status', 'finalized')
            ->whereNotNull('actual_collection_date')
            ->whereHas('wasteStreams')
            ->get();

        $moved = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            $requested = $order->requested_collection_date ? \Carbon\Carbon::parse($order->requested_collection_date) : null;
            $actual = \Carbon\Carbon::parse($order->actual_collection_date);

            if (! $requested || ($requested->year === $actual->year && $requested->month === $actual->month)) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    'Would move order %s: %s → %s (requested %s, actual %s)',
                    $order->tracking_number,
                    $requested->format('Y-m'),
                    $actual->format('Y-m'),
                    $requested->format('Y-m-d'),
                    $actual->format('Y-m-d')
                ));
                $moved++;
                continue;
            }

            $service->moveOrderSummariesToActualCollectionDate($order, null);
            $moved++;
        }

        $this->info("Processed {$orders->count()} finalized orders with waste streams.");
        $this->info("Moved weights to actual collection month: {$moved}. Skipped (same month): {$skipped}.");

        return self::SUCCESS;
    }
}
