<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\RecurringOrder;
use App\Notifications\RecurringOrdersSummaryNotification;
use App\Services\CommunicatorSmsClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CreateRecurringOrdersCommand extends Command
{
    protected $signature = 'recurring-orders:create {--date= : Date to create orders for (Y-m-d), defaults to today}';

    protected $description = 'Create pending orders from active recurring order templates for the given day (default: tomorrow). Runs daily at 13:00 Africa/Johannesburg.';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::createFromFormat('Y-m-d', $this->option('date'))
            : Carbon::tomorrow();

        $dayName = strtolower($date->format('l'));

        $this->info("Creating recurring orders for {$date->toDateString()} ({$dayName})…");

        $templates = RecurringOrder::with(['company', 'branch', 'site', 'serviceProvider'])
            ->where('is_active', true)
            ->get()
            ->filter(fn ($t) => $t->firesOnDay($dayName));

        $createdRows = [];
        $skippedRows = [];

        foreach ($templates as $template) {
            $alreadyExists = Order::where('recurring_order_id', $template->id)
                ->whereDate('requested_collection_date', $date->toDateString())
                ->exists();

            if ($alreadyExists) {
                $skippedRows[] = $this->templateRow($template);
                $this->line("  Skipped #{$template->id} — order already exists for {$date->toDateString()}.");

                continue;
            }

            DB::transaction(function () use ($template, $date, &$createdRows) {
                $totalQuantity = collect($template->quantity_lines)->sum('quantity');

                $order = Order::create([
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

                $createdRows[] = array_merge($this->templateRow($template), [
                    'tracking_number' => $order->tracking_number,
                ]);
            });
        }

        $this->info('Done. Created: '.count($createdRows).', Skipped (already existed): '.count($skippedRows).'.');

        $this->sendSummaryEmail($date->toDateString(), $createdRows, $skippedRows);

        return self::SUCCESS;
    }

    private function templateRow(RecurringOrder $template): array
    {
        $containers = collect($template->quantity_lines)
            ->map(fn ($l) => $l['quantity'].'× '.($l['container_option_name'] ?? ''))
            ->join(', ');

        return [
            'company' => $template->company?->name ?? '—',
            'branch' => $template->branch?->name,
            'site' => $template->site?->name,
            'order_type' => $template->order_type,
            'service_provider' => $template->serviceProvider?->name ?? '—',
            'containers' => $containers,
            'tracking_number' => null,
        ];
    }

    private function sendSummaryEmail(string $date, array $created, array $skipped): void
    {
        $recipients = config('recurring_orders.notify_emails', []);

        if (empty($recipients)) {
            $this->line('  No RECURRING_ORDERS_NOTIFY_EMAILS configured — skipping summary email.');
        } else {
            $notification = new RecurringOrdersSummaryNotification($date, $created, $skipped);

            foreach ($recipients as $email) {
                try {
                    Notification::route('mail', $email)->notify($notification);
                } catch (\Throwable $e) {
                    $this->error("  Failed to send summary email to {$email}: ".$e->getMessage());
                    report($e);
                }
            }

            $this->info('  Summary email sent to: '.implode(', ', $recipients));
        }

        $this->sendSummarySms($date, $created, $skipped);
    }

    private function sendSummarySms(string $date, array $created, array $skipped): void
    {
        $to = config('recurring_orders.sms_to');

        if (! $to) {
            return;
        }

        if (! config('communicator.enabled', false)) {
            $this->line('  RECURRING_ORDERS_SMS_TO is set but COMMUNICATOR_ENABLED is false — skipping summary SMS.');

            return;
        }

        $message = "WasteFlow: scheduled recurring orders created for {$date} — "
            .count($created).' created, '.count($skipped).' skipped.';

        try {
            app(CommunicatorSmsClient::class)->send($to, $message);
            $this->info("  Summary SMS sent to: {$to}");
        } catch (\Throwable $e) {
            $this->error('  Failed to send summary SMS: '.$e->getMessage());
            report($e);
        }
    }
}
