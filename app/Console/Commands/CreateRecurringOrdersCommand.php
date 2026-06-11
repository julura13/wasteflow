<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\RecurringOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateRecurringOrdersCommand extends Command
{
    protected $signature = 'recurring-orders:create {--date= : Date to create orders for (Y-m-d), defaults to today}';

    protected $description = 'Create pending orders from active recurring order templates for the given day (default: today). Runs daily at 04:00.';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', $this->option('date'))
            : Carbon::today();

        $dayName = strtolower($date->format('l')); // e.g. 'monday'

        $this->info("Creating recurring orders for {$date->toDateString()} ({$dayName})…");

        $templates = RecurringOrder::with(['company', 'branch', 'site', 'serviceProvider'])
            ->where('is_active', true)
            ->get()
            ->filter(fn ($t) => $t->firesOnDay($dayName));

        $created = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            // Skip if an order was already created from this template today
            $alreadyExists = Order::where('recurring_order_id', $template->id)
                ->whereDate('requested_collection_date', $date->toDateString())
                ->exists();

            if ($alreadyExists) {
                $skipped++;
                $this->line("  Skipped #{$template->id} — order already exists for {$date->toDateString()}.");

                continue;
            }

            DB::transaction(function () use ($template, $date, &$created) {
                $totalQuantity = collect($template->quantity_lines)->sum('quantity');

                Order::create([
                    'company_id' => $template->company_id,
                    'branch_id' => $template->branch_id,
                    'site_id' => $template->site_id,
                    'service_provider_id' => $template->service_provider_id,
                    'created_by' => $template->created_by,
                    'recurring_order_id' => $template->id,
                    'order_type' => $template->order_type,
                    'status' => 'pending',
                    'quantity_lines' => $template->quantity_lines,
                    'estimated_quantity' => $totalQuantity,
                    'requested_collection_date' => $date->toDateString(),
                    'notes' => $template->notes,
                ]);

                $created++;
            });
        }

        $this->info("Done. Created: {$created}, Skipped (already existed): {$skipped}.");

        return self::SUCCESS;
    }
}
