<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateOrderIndexExportJob;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderIndexExport;
use App\Models\ServiceProvider;
use App\Services\OrdersIndexQueryService;
use App\Traits\ScopeByClientTrait;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderExportController extends Controller
{
    use ScopeByClientTrait;

    public function __construct(
        protected OrdersIndexQueryService $ordersIndexQueryService,
    ) {}

    public function downloadPDF(Order $order)
    {
        $this->ensureOrderInScope($order);

        try {
            ini_set('memory_limit', '256M');
            set_time_limit(30);

            $order->load([
                'site.branch.company',
                'company',
                'branch',
                'creator',
                'serviceProvider',
                'wasteStreams.material.grade',
                'wasteStreams.material.wasteStream',
            ]);

            $order->append('total_rebate');

            $options = new Options;
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $html = view('orders.pdf', ['order' => $order])->render();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'Order_'.$order->tracking_number.'_'.now()->format('Y-m-d').'.pdf';
            $output = $dompdf->output();

            unset($dompdf, $html, $order);

            return response()->streamDownload(function () use ($output) {
                echo $output;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: '.$e->getMessage());

            return back()->withErrors(['pdf' => 'Failed to generate PDF. Please try again.']);
        }
    }

    public function requestOrderIndexExport(Request $request)
    {
        $validated = $request->validate([
            'format' => ['required', 'string', Rule::in([OrderIndexExport::FORMAT_CSV, OrderIndexExport::FORMAT_PDF])],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'order_types' => ['nullable', 'array'],
            'order_types.*' => ['string', Rule::in(['waste', 'recycling'])],
            'service_provider_ids' => ['nullable', 'array'],
            'service_provider_ids.*' => ['integer', 'min:1'],
            'requested_collection_from' => ['nullable', 'string'],
            'requested_collection_to' => ['nullable', 'string'],
            'hide_service_provider' => ['sometimes', 'boolean'],
        ]);

        $orderTypes = $validated['order_types'] ?? [];
        $orderTypes = array_values(array_unique(array_filter(array_map('strtolower', $orderTypes), fn ($t) => in_array($t, ['waste', 'recycling'], true))));

        $serviceProviderIds = array_values(array_unique(array_filter(array_map('intval', $validated['service_provider_ids'] ?? []), fn ($id) => $id > 0)));

        [$requestedCollectionFrom, $requestedCollectionTo] = $this->ordersIndexQueryService->parseRequestedCollectionDateRangeInput(
            $validated['requested_collection_from'] ?? null,
            $validated['requested_collection_to'] ?? null,
        );

        $ext = $validated['format'] === OrderIndexExport::FORMAT_CSV ? 'csv' : 'pdf';
        $filename = 'orders_export_'.now()->format('Y-m-d_His').'.'.$ext;
        $uuid = (string) Str::uuid();

        $export = OrderIndexExport::query()->create([
            'uuid' => $uuid,
            'user_id' => $request->user()->id,
            'format' => $validated['format'],
            'status' => OrderIndexExport::STATUS_PENDING,
            'disk' => 'local',
            'filename' => $filename,
            'filters' => [
                'search' => $validated['search'] ?? null,
                'status' => $validated['status'] ?? null,
                'order_types' => $orderTypes,
                'service_provider_ids' => $serviceProviderIds,
                'requested_collection_from' => $requestedCollectionFrom,
                'requested_collection_to' => $requestedCollectionTo,
                'hide_service_provider' => $request->boolean('hide_service_provider'),
            ],
            'expires_at' => now()->addDay(),
        ]);

        GenerateOrderIndexExportJob::dispatch($export->id)->afterResponse();

        return back()
            ->with('order_export_uuid', $uuid)
            ->with('order_export_format', $validated['format']);
    }

    public function orderIndexExportStatus(Request $request, string $uuid)
    {
        $export = OrderIndexExport::query()
            ->where([
                'uuid' => $uuid,
                'user_id' => $request->user()->id,
            ])
            ->firstOrFail();

        return response()->json([
            'status' => $export->status,
            'download_url' => $export->status === OrderIndexExport::STATUS_COMPLETED
                ? route('orders.export.download', ['uuid' => $uuid])
                : null,
            'error_message' => $export->error_message,
        ]);
    }

    public function downloadOrderIndexExport(Request $request, string $uuid)
    {
        $export = OrderIndexExport::query()
            ->where([
                'uuid' => $uuid,
                'user_id' => $request->user()->id,
            ])
            ->firstOrFail();

        if ($export->status !== OrderIndexExport::STATUS_COMPLETED) {
            abort(404, 'This export is not ready yet.');
        }

        if ($export->expires_at->isPast()) {
            abort(410, 'This download link has expired.');
        }

        if ($export->path === null || ! Storage::disk($export->disk)->exists($export->path)) {
            abort(404, 'The export file is no longer available.');
        }

        return Storage::disk($export->disk)->download($export->path, $export->filename);
    }

    public function getServiceProvidersByDate(Request $request)
    {
        $validated = $request->validate([
            'collection_date' => 'required|date',
        ]);

        $collectionDate = Carbon::parse($validated['collection_date']);

        $serviceProviderIds = Order::where('requested_collection_date', $collectionDate->format('Y-m-d'))
            ->whereNotNull('service_provider_id')
            ->distinct()
            ->pluck('service_provider_id');

        $serviceProviders = ServiceProvider::whereIn('id', $serviceProviderIds)
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json($serviceProviders);
    }

    public function downloadConsolidatedPDF(Request $request)
    {
        abort_unless(auth()->user()->can('orders-generate-consolidated'), 403, 'You do not have permission to generate consolidated orders.');

        $validated = $request->validate([
            'collection_date' => 'required|date',
            'service_provider_id' => 'required|exists:service_providers,id',
        ]);

        $collectionDate = Carbon::parse($validated['collection_date']);
        $serviceProvider = ServiceProvider::findOrFail($validated['service_provider_id']);

        $orders = Order::with(['site.branch.company', 'company', 'branch'])
            ->where('requested_collection_date', $collectionDate->format('Y-m-d'))
            ->where('service_provider_id', $serviceProvider->id)
            ->orderBy('created_at')
            ->get();

        foreach ($orders as $order) {
            if ($order->status === 'pending') {
                $order->update(['status' => 'scheduled']);
                ActivityLog::log('order_consolidated_pdf_scheduled', "Order {$order->tracking_number} set to scheduled via consolidated PDF", $order, [
                    'tracking_number' => $order->tracking_number,
                    'collection_date' => $collectionDate->format('Y-m-d'),
                    'service_provider_id' => $serviceProvider->id,
                ]);
            }
        }

        $orders->fresh();
        $orders->load(['site.branch.company', 'company', 'branch']);

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('orders.consolidated-pdf', [
            'orders' => $orders,
            'serviceProvider' => $serviceProvider,
            'collectionDate' => $collectionDate,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Consolidated_Order_'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $serviceProvider->name).'_'.$collectionDate->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
