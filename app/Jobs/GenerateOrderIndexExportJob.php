<?php

namespace App\Jobs;

use App\Models\OrderIndexExport;
use App\Models\User;
use App\Services\OrdersIndexQueryService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateOrderIndexExportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(public int $orderIndexExportId) {}

    public function handle(OrdersIndexQueryService $ordersIndexQueryService): void
    {
        $export = OrderIndexExport::query()->find($this->orderIndexExportId);
        if (! $export) {
            return;
        }

        if ($export->expires_at->isPast()) {
            $export->update([
                'status' => OrderIndexExport::STATUS_FAILED,
                'error_message' => 'This export request has expired. Please generate the export again.',
            ]);

            return;
        }

        $export->update(['status' => OrderIndexExport::STATUS_PROCESSING, 'error_message' => null]);

        try {
            $user = User::query()->findOrFail($export->user_id);
            $filters = $export->filters;

            [$requestedCollectionFrom, $requestedCollectionTo] = $ordersIndexQueryService->parseRequestedCollectionDateRangeInput(
                $filters['requested_collection_from'] ?? null,
                $filters['requested_collection_to'] ?? null,
            );

            $orderTypes = $filters['order_types'] ?? [];
            if (! is_array($orderTypes)) {
                $orderTypes = [];
            }
            $orderTypes = array_values(array_unique(array_filter(array_map('strtolower', $orderTypes), fn ($t) => in_array($t, ['waste', 'recycling'], true))));

            $baseQuery = $ordersIndexQueryService->buildForUser(
                $user,
                isset($filters['search']) ? (string) $filters['search'] : null,
                isset($filters['status']) && $filters['status'] !== '' ? (string) $filters['status'] : null,
                $orderTypes,
                $requestedCollectionFrom,
                $requestedCollectionTo,
            );

            $query = $ordersIndexQueryService->applyExportOrdering($baseQuery);

            $orders = $query->get();

            $relativePath = match ($export->format) {
                OrderIndexExport::FORMAT_CSV => 'order-index-exports/'.$export->uuid.'.csv',
                OrderIndexExport::FORMAT_PDF => 'order-index-exports/'.$export->uuid.'.pdf',
                default => throw new \InvalidArgumentException('Invalid export format.'),
            };

            if ($export->format === OrderIndexExport::FORMAT_CSV) {
                $stream = fopen('php://temp', 'r+');
                if ($stream === false) {
                    throw new \RuntimeException('Could not open buffer for CSV export.');
                }
                fputcsv($stream, ['Tracking Number', 'Company', 'Branch', 'Site', 'Service Provider', 'Order Type', 'Status', 'Requested Date', 'Actual Date', 'Slip Number']);
                foreach ($orders as $order) {
                    $site = $order->site;
                    $branch = $site?->branch ?? $order->branch;
                    $company = $branch?->company ?? $order->company;
                    fputcsv($stream, [
                        $order->tracking_number,
                        $company?->name ?? '',
                        $branch?->name ?? '',
                        $site?->name ?? '',
                        $order->serviceProvider?->name ?? (is_string($order->service_provider) ? $order->service_provider : ''),
                        $order->order_type === 'waste' ? 'Waste Order' : 'Recycling Order',
                        $order->status,
                        $order->requested_collection_date?->format('Y-m-d') ?? '',
                        $order->actual_collection_date?->format('Y-m-d') ?? '',
                        $order->slip_number ?? '',
                    ]);
                }
                rewind($stream);
                Storage::disk($export->disk)->put($relativePath, stream_get_contents($stream) ?: '');
                fclose($stream);
            } else {
                $options = new Options;
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', false);
                $options->set('defaultFont', 'DejaVu Sans');

                $dompdf = new Dompdf($options);
                $html = view('orders.export-pdf', ['orders' => $orders])->render();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();

                Storage::disk($export->disk)->put($relativePath, $dompdf->output());
            }

            $export->update([
                'status' => OrderIndexExport::STATUS_COMPLETED,
                'path' => $relativePath,
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            report($e);
            $export->update([
                'status' => OrderIndexExport::STATUS_FAILED,
                'error_message' => 'The export could not be generated. Please try again or contact support if the problem continues.',
            ]);
        }
    }
}
