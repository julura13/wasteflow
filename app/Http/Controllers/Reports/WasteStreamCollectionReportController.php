<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateWasteStreamCollectionPdfJob;
use App\Models\OrderWasteStream;
use App\Models\Site;
use App\Models\WasteStreamCollectionReportExport;
use App\Services\CompanyUserService;
use App\Services\RebateTrackerReportService;
use App\Traits\ScopeByClientTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WasteStreamCollectionReportController extends Controller
{
    use ScopeByClientTrait;

    public function __construct(
        protected CompanyUserService $companyUserService,
        protected RebateTrackerReportService $rebateTrackerReportService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $companyIds = $user->isAdmin() ? [] : $this->companyUserService->getCompanyIdsForUser($user);

        if (! $user->isAdmin() && empty($companyIds)) {
            abort(403, 'No company assigned. Please contact administrator.');
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $wasteStreamBreakdown = $this->rebateTrackerReportService->getWasteStreamGradeBreakdown($startDate, $endDate, $companyId, $branchId, $siteId, $user, $companyIds);

        $companies = $this->scopeCompaniesForUser();

        return Inertia::render('Reports/WasteStreamCollectionReport', [
            'wasteStreamBreakdown' => $wasteStreamBreakdown,
            'companies' => $companies,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
            ],
            'totalWeight' => $wasteStreamBreakdown->sum('subtotal_weight'),
        ]);
    }

    public function requestPdf(Request $request)
    {
        $user = $request->user();
        $companyIds = $user->isAdmin() ? [] : $this->companyUserService->getCompanyIdsForUser($user);

        if (! $user->isAdmin() && empty($companyIds)) {
            abort(403, 'No company assigned. Please contact administrator.');
        }

        $request->merge([
            'company_id' => $request->input('company_id') === '' || $request->input('company_id') === null ? null : $request->input('company_id'),
            'branch_id' => $request->input('branch_id') === '' || $request->input('branch_id') === null ? null : $request->input('branch_id'),
            'site_id' => $request->input('site_id') === '' || $request->input('site_id') === null ? null : $request->input('site_id'),
        ]);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
        ]);

        $companyId = $validated['company_id'] ?? null;
        $branchId = $validated['branch_id'] ?? null;
        $siteId = $validated['site_id'] ?? null;

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $uuid = (string) Str::uuid();
        $filename = 'Waste_Stream_Collection_Report_'.$validated['start_date'].'_to_'.$validated['end_date'].'.pdf';

        $export = WasteStreamCollectionReportExport::query()->create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'status' => WasteStreamCollectionReportExport::STATUS_PENDING,
            'disk' => 'local',
            'filename' => $filename,
            'filters' => [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
            ],
            'expires_at' => now()->addDay(),
        ]);

        GenerateWasteStreamCollectionPdfJob::dispatch($export->id)->afterResponse();

        return back()->with('success', 'Your PDF report is being prepared. You can download it when it is ready using the button below.')
            ->with('waste_stream_collection_pdf_export_uuid', $uuid);
    }

    public function pdfStatus(Request $request, string $uuid)
    {
        $export = WasteStreamCollectionReportExport::query()
            ->where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status' => $export->status,
            'download_url' => $export->status === WasteStreamCollectionReportExport::STATUS_COMPLETED
                ? route('reports.waste-stream-collection-pdf.download', ['uuid' => $uuid])
                : null,
            'error_message' => $export->error_message,
        ]);
    }

    public function downloadPdf(Request $request, string $uuid)
    {
        $export = WasteStreamCollectionReportExport::query()
            ->where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($export->status !== WasteStreamCollectionReportExport::STATUS_COMPLETED) {
            abort(404, 'This report is not ready yet.');
        }

        if ($export->expires_at->isPast()) {
            abort(410, 'This download link has expired.');
        }

        if ($export->path === null || ! Storage::disk($export->disk)->exists($export->path)) {
            abort(404, 'The report file is no longer available.');
        }

        return Storage::disk($export->disk)->download($export->path, $export->filename);
    }

    public function getAverageWeightForWheelieBins(Request $request)
    {
        $validated = $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'site_id' => 'nullable|exists:sites,id',
            'container_type' => 'nullable|in:rel_skip,wheelie_bins,skips_30m2',
        ]);

        if (empty($validated['month'])) {
            $validated['month'] = Carbon::now()->format('Y-m');
        }

        $containerType = $validated['container_type'] ?? 'wheelie_bins';

        $startDate = Carbon::parse($validated['month'])->startOfMonth();
        $endDate = Carbon::parse($validated['month'])->endOfMonth();

        $containerTypeLabels = [
            'rel_skip' => 'REL Skip',
            'wheelie_bins' => 'Wheelie Bins',
            'skips_30m2' => '30m² Skips',
        ];

        $user = $request->user();
        $companyIds = $user->isAdmin() ? [] : $this->companyUserService->getCompanyIdsForUser($user);

        if (! $user->isAdmin() && empty($companyIds)) {
            abort(403, 'No company assigned. Please contact administrator.');
        }

        $query = OrderWasteStream::with(['order.site', 'material'])
            ->whereHas('order', function ($q) use ($startDate, $endDate, $validated, $containerType, $companyIds, $user) {
                $q->where('status', 'finalized')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('actual_collection_date', [$startDate, $endDate])
                            ->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->whereNull('actual_collection_date')
                                    ->whereBetween('requested_collection_date', [$startDate, $endDate]);
                            });
                    })
                    ->where(function ($query) use ($containerType) {
                        $query->whereJsonContains('quantity_lines', [['quantity_type' => $containerType]])
                            ->orWhere('quantity_type', $containerType);
                    });
                if (isset($validated['site_id'])) {
                    $q->where('site_id', $validated['site_id']);
                }
                if (! $user->isAdmin()) {
                    $q->whereHas('site.branch.company', function ($q) use ($companyIds) {
                        $q->whereIn('companies.id', $companyIds);
                    });
                }
            });

        $streams = $query->get();

        $sites = Site::with(['branch.company'])
            ->where('is_active', true)
            ->when(! $user->isAdmin(), function ($query) use ($companyIds) {
                $query->whereHas('branch.company', function ($q) use ($companyIds) {
                    $q->whereIn('companies.id', $companyIds);
                });
            })
            ->orderBy('name')
            ->get();

        if ($streams->isEmpty()) {
            return Inertia::render('Reports/AverageWeight', [
                'averageWeightData' => null,
                'sites' => $sites,
                'containerTypes' => $containerTypeLabels,
                'filters' => [
                    'month' => $validated['month'],
                    'site_id' => $validated['site_id'] ?? null,
                    'container_type' => $containerType,
                ],
            ]);
        }

        $totalWeight = $streams->sum('nett_weight');

        $ordersWithContainers = $streams->pluck('order')->unique('id');
        $totalContainers = $ordersWithContainers->sum(function ($order) use ($containerType) {
            if ($order->quantity_type === $containerType) {
                return $order->quantity ?? 0;
            }
            $quantityLines = $order->quantity_lines ?? [];
            if (is_array($quantityLines)) {
                $containerLine = collect($quantityLines)->firstWhere('quantity_type', $containerType);

                return $containerLine['quantity'] ?? 0;
            }

            return 0;
        });

        $averageWeight = $totalContainers > 0 ? $totalWeight / $totalContainers : 0;

        return Inertia::render('Reports/AverageWeight', [
            'averageWeightData' => $totalContainers > 0 ? [
                'month' => $validated['month'],
                'container_type' => $containerType,
                'container_type_label' => $containerTypeLabels[$containerType] ?? $containerType,
                'total_weight' => round($totalWeight, 3),
                'total_containers' => $totalContainers,
                'average_weight_per_container' => round($averageWeight, 3),
            ] : null,
            'sites' => $sites,
            'containerTypes' => $containerTypeLabels,
            'filters' => [
                'month' => $validated['month'],
                'site_id' => $validated['site_id'] ?? null,
                'container_type' => $containerType,
            ],
        ]);
    }
}
