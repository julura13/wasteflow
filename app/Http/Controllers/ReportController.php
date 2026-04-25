<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateWasteManagementPdfJob;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use App\Models\WasteManagementReportExport;
use App\Services\CarbonCalculator;
use App\Services\ChartImageService;
use App\Services\CustomerOrderFrequencyReportService;
use App\Services\LandfillSpaceCalculator;
use App\Services\LifecycleCarbonEquivalency;
use App\Services\OrderWasteStreamReportingService;
use App\Services\WasteImpactCalculator;
use App\Services\WasteManagementReportPdfGenerator;
use App\Services\WaterCalculator;
use App\Support\DisplayDate;
use App\Traits\ScopeByClientTrait;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ReportController extends Controller
{
    use ScopeByClientTrait;

    protected ChartImageService $chartService;

    protected WasteImpactCalculator $wasteImpactCalculator;

    protected CarbonCalculator $carbonCalculator;

    protected LandfillSpaceCalculator $landfillSpaceCalculator;

    protected WaterCalculator $waterCalculator;

    protected LifecycleCarbonEquivalency $lifecycleCarbonEquivalency;

    protected OrderWasteStreamReportingService $orderWasteStreamReporting;

    protected CustomerOrderFrequencyReportService $customerOrderFrequencyReport;

    protected WasteManagementReportPdfGenerator $wasteManagementReportPdfGenerator;

    public function __construct(
        ChartImageService $chartService,
        WasteImpactCalculator $wasteImpactCalculator,
        CarbonCalculator $carbonCalculator,
        LandfillSpaceCalculator $landfillSpaceCalculator,
        WaterCalculator $waterCalculator,
        LifecycleCarbonEquivalency $lifecycleCarbonEquivalency,
        OrderWasteStreamReportingService $orderWasteStreamReporting,
        CustomerOrderFrequencyReportService $customerOrderFrequencyReport,
        WasteManagementReportPdfGenerator $wasteManagementReportPdfGenerator,
    ) {
        $this->chartService = $chartService;
        $this->wasteImpactCalculator = $wasteImpactCalculator;
        $this->carbonCalculator = $carbonCalculator;
        $this->landfillSpaceCalculator = $landfillSpaceCalculator;
        $this->waterCalculator = $waterCalculator;
        $this->lifecycleCarbonEquivalency = $lifecycleCarbonEquivalency;
        $this->orderWasteStreamReporting = $orderWasteStreamReporting;
        $this->customerOrderFrequencyReport = $customerOrderFrequencyReport;
        $this->wasteManagementReportPdfGenerator = $wasteManagementReportPdfGenerator;
    }

    /**
     * Customer order frequency: last finalized order and average finalized orders per month (waste vs recycling).
     */
    public function customerOrderFrequencies(Request $request)
    {
        [$lookbackMonths, $rows] = $this->customerOrderFrequencyReportPayload($request);

        return Inertia::render('Reports/CustomerOrderFrequencies', [
            'rows' => $rows,
            'lookback_months' => $lookbackMonths,
        ]);
    }

    /**
     * CSV export for customer order frequency report (same scope and lookback as the on-screen report).
     */
    public function customerOrderFrequenciesExport(Request $request)
    {
        [$lookbackMonths, $rows] = $this->customerOrderFrequencyReportPayload($request);

        $filename = 'customer_order_frequencies_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($rows, $lookbackMonths) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Customer',
                'Lookback months',
                'Waste last finalized',
                'Waste days since last',
                'Waste finalized in period',
                'Waste avg per month',
                'Recycling last finalized',
                'Recycling days since last',
                'Recycling finalized in period',
                'Recycling avg per month',
            ]);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['company_name'],
                    $lookbackMonths,
                    DisplayDate::formatOrEmpty($row['waste']['last_finalized_date'] ?? null),
                    $row['waste']['days_since_last_finalized'] ?? '',
                    $row['waste']['finalized_orders_in_period'],
                    $row['waste']['average_orders_per_month'],
                    DisplayDate::formatOrEmpty($row['recycling']['last_finalized_date'] ?? null),
                    $row['recycling']['days_since_last_finalized'] ?? '',
                    $row['recycling']['finalized_orders_in_period'],
                    $row['recycling']['average_orders_per_month'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * PDF export for customer order frequency report (same scope and lookback as the on-screen report).
     */
    public function customerOrderFrequenciesExportPdf(Request $request)
    {
        [$lookbackMonths, $rows] = $this->customerOrderFrequencyReportPayload($request);

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('reports.customer-order-frequencies-pdf', [
            'rows' => $rows,
            'lookbackMonths' => $lookbackMonths,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'customer_order_frequencies_'.now()->format('Y-m-d_His').'.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array{0: int, 1: list<array<string, mixed>>}
     */
    private function customerOrderFrequencyReportPayload(Request $request): array
    {
        $validated = $request->validate([
            'lookback_months' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ]);
        $lookbackMonths = (int) ($validated['lookback_months'] ?? 12);

        $companies = $this->scopeCompaniesForUser();
        $rows = $this->customerOrderFrequencyReport->buildForCompanies($companies, $lookbackMonths, now());

        return [$lookbackMonths, $rows];
    }

    /**
     * Waste management report filter form (PDF is generated asynchronously).
     */
    public function wasteManagement(Request $request)
    {
        $companies = $this->scopeCompaniesForUser();

        return Inertia::render('Reports/WasteManagement', [
            'companies' => $companies,
            'filters' => [
                'company_id' => $request->input('company_id') ?? '',
                'branch_id' => $request->input('branch_id') ?? '',
                'site_id' => $request->input('site_id') ?? '',
                'month' => (int) ($request->input('month') ?? date('m')),
                'year' => (int) ($request->input('year') ?? date('Y')),
            ],
        ]);
    }

    public function requestWasteManagementPdf(Request $request)
    {
        $user = $request->user();

        $request->merge([
            'company_id' => $request->input('company_id') === '' || $request->input('company_id') === null ? null : $request->input('company_id'),
            'branch_id' => $request->input('branch_id') === '' || $request->input('branch_id') === null ? null : $request->input('branch_id'),
            'site_id' => $request->input('site_id') === '' || $request->input('site_id') === null ? null : $request->input('site_id'),
        ]);

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $companyId = (int) $validated['company_id'];
        $branchId = isset($validated['branch_id']) && $validated['branch_id'] !== null ? (int) $validated['branch_id'] : null;
        $siteId = isset($validated['site_id']) && $validated['site_id'] !== null ? (int) $validated['site_id'] : null;

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $uuid = (string) Str::uuid();
        $filename = sprintf(
            'WasteFlow_Resource_Intelligence_Report_%d-%02d.pdf',
            (int) $validated['year'],
            (int) $validated['month'],
        );

        $export = WasteManagementReportExport::query()->create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'status' => WasteManagementReportExport::STATUS_PENDING,
            'disk' => 'local',
            'filename' => $filename,
            'filters' => [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
                'month' => (int) $validated['month'],
                'year' => (int) $validated['year'],
            ],
            'expires_at' => now()->addDay(),
        ]);

        GenerateWasteManagementPdfJob::dispatch($export->id)->afterResponse();

        return back()->with('success', 'Your PDF report is being prepared. You can download it when it is ready using the button below.')
            ->with('waste_management_pdf_export_uuid', $uuid);
    }

    public function wasteManagementPdfStatus(Request $request, string $uuid)
    {
        $export = WasteManagementReportExport::query()
            ->where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status' => $export->status,
            'download_url' => $export->status === WasteManagementReportExport::STATUS_COMPLETED
                ? route('reports.waste-management-pdf.download', ['uuid' => $uuid])
                : null,
            'error_message' => $export->error_message,
        ]);
    }

    public function downloadWasteManagementPdf(Request $request, string $uuid)
    {
        $export = WasteManagementReportExport::query()
            ->where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($export->status !== WasteManagementReportExport::STATUS_COMPLETED) {
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

    /**
     * Build and store the PDF for a queued export (invoked from {@see GenerateWasteManagementPdfJob}).
     */
    public function completeWasteManagementReportExport(WasteManagementReportExport $export, User $user): void
    {
        $filters = $export->filters;
        $companyId = $filters['company_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        $siteId = $filters['site_id'] ?? null;
        $month = (int) ($filters['month'] ?? date('m'));
        $year = (int) ($filters['year'] ?? date('Y'));

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScopeForUser($user, $companyId ? (int) $companyId : null, $branchId ? (int) $branchId : null, $siteId ? (int) $siteId : null);

        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::with('company')->find($branchId) : null;
        $site = $siteId ? Site::with('branch.company')->find($siteId) : null;

        [$pdfFilename, $binary] = $this->buildWasteManagementPdfBinary($company, $branch, $site, $month, $year);

        $relativePath = 'waste-management-reports/'.$export->uuid.'.pdf';
        Storage::disk($export->disk)->put($relativePath, $binary);

        $export->update([
            'status' => WasteManagementReportExport::STATUS_COMPLETED,
            'path' => $relativePath,
            'filename' => $pdfFilename,
            'error_message' => null,
        ]);
    }

    /**
     * @return array{0: string, 1: string} Filename and raw PDF bytes
     */
    private function buildWasteManagementPdfBinary(?Company $company, ?Branch $branch, ?Site $site, int $month, int $year): array
    {
        $reportData = $this->getReportData($company, $branch, $site, $month, $year);
        $chartPaths = $this->generateCharts($reportData);

        $montserratReg = public_path('fonts/Montserrat-Regular.ttf');
        $montserratBold = public_path('fonts/Montserrat-Bold.ttf');
        $html = view('reports.waste-management-pdf', [
            'reportData' => $reportData,
            'chartPaths' => $chartPaths,
            'pdfFontRegularUrl' => is_file($montserratReg)
                ? 'file://'.str_replace('\\', '/', $montserratReg)
                : null,
            'pdfFontBoldUrl' => is_file($montserratBold)
                ? 'file://'.str_replace('\\', '/', $montserratBold)
                : null,
        ])->render();

        $filename = sprintf('WasteFlow_Resource_Intelligence_Report_%04d-%02d.pdf', $year, $month);

        return [$filename, $this->wasteManagementReportPdfGenerator->generate($html)];
    }

    /**
     * Interactive web view of the Resource Intelligence Report.
     */
    public function resourceIntelligenceView(Request $request)
    {
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;
        $month = (int) ($request->input('month', (int) date('m')));
        $year = (int) ($request->input('year', (int) date('Y')));

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::with('company')->find($branchId) : null;
        $site = $siteId ? Site::with('branch.company')->find($siteId) : null;

        $reportData = $this->getReportData($company, $branch, $site, $month, $year);
        $companies = $this->scopeCompaniesForUser();

        return Inertia::render('Reports/ResourceIntelligence', [
            'reportData' => $reportData,
            'companies' => $companies,
            'filters' => [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
                'month' => $month,
                'year' => $year,
            ],
        ]);
    }

    /**
     * Display report data summary (JSON dump for testing)
     */
    public function wasteManagementSummary(Request $request)
    {
        $companies = $this->scopeCompaniesForUser();

        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::find($branchId) : null;
        $site = $siteId ? Site::find($siteId) : null;

        $reportData = $this->getReportData($company, $branch, $site, (int) $month, (int) $year);

        return Inertia::render('Reports/WasteManagementSummary', [
            'reportData' => $reportData,
            'companies' => $companies,
            'filters' => [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
                'month' => $month,
                'year' => $year,
            ],
        ]);
    }

    /**
     * Display the carbon calculator proofing page (manual weights, same formulas as reports).
     */
    public function carbonCalculator()
    {
        return Inertia::render('Reports/CarbonCalculator');
    }

    /**
     * Run carbon calculation from manually entered weights (same logic as reports).
     */
    public function carbonCalculatorCalculate(Request $request)
    {
        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.paper' => ['nullable', 'numeric', 'min:0'],
            'weights.plasticPPHD' => ['nullable', 'numeric', 'min:0'],
            'weights.plasticPS' => ['nullable', 'numeric', 'min:0'],
            'weights.plasticLDPE' => ['nullable', 'numeric', 'min:0'],
            'weights.aluminium' => ['nullable', 'numeric', 'min:0'],
            'weights.steel' => ['nullable', 'numeric', 'min:0'],
            'weights.glass' => ['nullable', 'numeric', 'min:0'],
            'weights.foodWaste' => ['nullable', 'numeric', 'min:0'],
            'weights.gardenWaste' => ['nullable', 'numeric', 'min:0'],
            'weights.batteries' => ['nullable', 'numeric', 'min:0'],
            'weights.electronics' => ['nullable', 'numeric', 'min:0'],
            'weights.tetrapak' => ['nullable', 'numeric', 'min:0'],
            'weights.wood' => ['nullable', 'numeric', 'min:0'],
        ]);

        $weights = array_map(fn ($v) => (float) ($v ?? 0), $validated['weights'] ?? []);

        $result = $this->carbonCalculator->calculateMaterialsCO2e($weights);

        return response()->json([
            'materials' => $result['materials'],
            'totals' => $result['totals'],
        ]);
    }

    /**
     * Display landfill space saved calculator (manual weights, same formulas as reports).
     */
    public function landfillSpaceCalculator()
    {
        return Inertia::render('Reports/LandfillSpaceCalculator');
    }

    /**
     * Run landfill space calculation from manually entered weights (same logic as reports).
     */
    public function landfillSpaceCalculatorCalculate(Request $request)
    {
        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.paper' => ['nullable', 'numeric', 'min:0'],
            'weights.plastics' => ['nullable', 'numeric', 'min:0'],
            'weights.aluminium' => ['nullable', 'numeric', 'min:0'],
            'weights.steel' => ['nullable', 'numeric', 'min:0'],
            'weights.glass' => ['nullable', 'numeric', 'min:0'],
            'weights.tetrapak' => ['nullable', 'numeric', 'min:0'],
            'weights.organics' => ['nullable', 'numeric', 'min:0'],
        ]);

        $weights = array_map(fn ($v) => (float) ($v ?? 0), $validated['weights'] ?? []);

        $breakdown = $this->landfillSpaceCalculator->calculate($weights);

        return response()->json([
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Display water saved calculator (manual weights, same formulas as reports).
     */
    public function waterCalculator()
    {
        return Inertia::render('Reports/WaterCalculator');
    }

    /**
     * Run water calculation from manually entered weights (same logic as reports).
     */
    public function waterCalculatorCalculate(Request $request)
    {
        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.paper' => ['nullable', 'numeric', 'min:0'],
            'weights.plastics' => ['nullable', 'numeric', 'min:0'],
            'weights.aluminium' => ['nullable', 'numeric', 'min:0'],
            'weights.steel' => ['nullable', 'numeric', 'min:0'],
            'weights.glass' => ['nullable', 'numeric', 'min:0'],
            'weights.tetrapak' => ['nullable', 'numeric', 'min:0'],
            'weights.organics' => ['nullable', 'numeric', 'min:0'],
        ]);

        $weights = array_map(fn ($v) => (float) ($v ?? 0), $validated['weights'] ?? []);

        $breakdown = $this->waterCalculator->calculate($weights);

        return response()->json([
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Get branches for a company (API endpoint)
     */
    public function getBranches(Request $request)
    {
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;

        if (! $companyId) {
            return response()->json([]);
        }

        if ($this->isClientScoped() && (int) auth()->user()->company_id !== $companyId) {
            return response()->json([]);
        }

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($branches);
    }

    /**
     * Get sites for a branch (API endpoint)
     */
    public function getSites(Request $request)
    {
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;

        if (! $branchId) {
            return response()->json([]);
        }

        if ($this->isClientScoped()) {
            $branch = Branch::where('id', $branchId)->where('company_id', auth()->user()->company_id)->first();
            if (! $branch) {
                return response()->json([]);
            }
        }

        $sites = Site::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($sites);
    }

    /**
     * Get report data for waste management report
     */
    private function getReportData(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null): array
    {
        // Determine the company name for display
        $companyName = 'XXXX';
        if ($site && $site->branch && $site->branch->company) {
            $companyName = $site->branch->company->name;
        } elseif ($branch && $branch->company) {
            $companyName = $branch->company->name;
        } elseif ($company) {
            $companyName = $company->name;
        }

        $scopeDisplayName = $companyName;
        if ($site) {
            $scopeDisplayName = $site->name;
        } elseif ($branch) {
            $scopeDisplayName = $branch->name;
        }

        $reportLocationLines = array_values(array_filter([
            $company ? 'Customer: '.$company->name : null,
            $branch ? 'Branch: '.$branch->name : null,
            $site ? 'Site: '.$site->name : null,
        ], static fn (?string $line): bool => $line !== null && $line !== ''));

        if ($reportLocationLines === []) {
            $reportLocationLines = [
                'No customer selected. Open Reports → WasteFlow Resource Intelligence Report, choose a customer and period, then use Download PDF so the link includes your filters.',
            ];
        }

        // Report period label and short date tag (yyyy/mm/dd; first day of month for tag)
        $reportDate = 'XXXX';
        $reportingPeriodLabel = 'Reporting period';
        if ($month && $year) {
            $start = Carbon::createFromDate($year, $month, 1);
            $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $end = Carbon::createFromDate($year, $month, $lastDay);
            $reportDate = $start->format(DisplayDate::CALENDAR);
            $reportingPeriodLabel = sprintf(
                'Reporting period (%s to %s)',
                $start->format(DisplayDate::CALENDAR),
                $end->format(DisplayDate::CALENDAR)
            );
        }

        $materialSummaries = ((! $company && ! $branch && ! $site) || ! $month || ! $year)
            ? collect()
            : $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        $wasteStreamTotals = $this->orderWasteStreamReporting->wasteStreamTotalsFromSummaries($materialSummaries);
        $classificationTotals = $this->orderWasteStreamReporting->classificationTotalsFromSummaries($materialSummaries);

        // Get material weights from finalized orders
        $materialWeights = $this->getMaterialWeights($company, $branch, $site, $month, $year);
        $grades = $this->getGrades($company, $branch, $site, $month, $year);
        $recyclingCommodities = $this->getRecyclingCommodities($materialWeights, 1);
        $recyclingCommodities2 = $this->getRecyclingCommodities($materialWeights, 2);

        // Calculate recyclingRecovered = sum of all recycling weights
        $recyclingRecovered = 0;
        foreach ($recyclingCommodities as $commodity) {
            $recyclingRecovered += (float) $commodity['qty'];
        }
        foreach ($recyclingCommodities2 as $commodity) {
            $recyclingRecovered += (float) $commodity['qty'];
        }
        $recyclingRecovered = round($recyclingRecovered, 2);

        // Get organicsRecovered from grades
        $organicsRecovered = $grades['organicsRecovered'];

        // Calculate totalIncomingWaste = generalWaste + organicsRecovered + recyclingRecovered
        $totalIncomingWaste = round($grades['generalWaste'] + $organicsRecovered + $recyclingRecovered, 2);

        // Calculate divertedFromLandfill = (recyclingRecovered + organicsRecovered) / totalIncomingWaste * 100
        $divertedFromLandfill = 0;
        if ($totalIncomingWaste > 0) {
            $divertedFromLandfill = round(($recyclingRecovered + $organicsRecovered) / $totalIncomingWaste * 100, 2);
        }

        // Calculate landfill space saved breakdown
        $landfillSpaceSavedData = $this->getLandfillSpaceSaved($company, $branch, $site, $month, $year, $organicsRecovered);
        $landfillSpaceSaved = $landfillSpaceSavedData['total'];

        // Calculate materials CO2e
        $materialsCO2eData = $this->getMaterialsCO2e($company, $branch, $site, $month, $year, $organicsRecovered);
        $materialsCO2e = $materialsCO2eData['materials'];
        $materialsCO2eTotals = $materialsCO2eData['totals'];

        // Calculate environmental impact (trees, energy, water, dashboard-style CO₂e + equivalencies)
        $environmentalImpact = $this->getEnvironmentalImpact($company, $branch, $site, $month, $year, $organicsRecovered);

        // Align carbon equivalency metrics with materials table lifecycle total (split plastics)
        $lifecycleKg = (float) ($materialsCO2eTotals['lifecycleSaving'] ?? 0);
        $reportEquivalency = $this->lifecycleCarbonEquivalency->fromLifecycleSavingKgCo2e($lifecycleKg);
        $environmentalImpact['electricityEquivalentKwhSaGrid'] = $reportEquivalency['electricityEquivalentKwhSaGrid'];
        $environmentalImpact['transportEquivalentKm'] = $reportEquivalency['transportEquivalentKm'];
        $environmentalImpact['fuelEquivalentLitresPetrol'] = $reportEquivalency['fuelEquivalentLitresPetrol'];
        $environmentalImpact['carsOffRoadAnnualEquivalent'] = $reportEquivalency['carsOffRoadAnnualEquivalent'];

        return [
            'companyName' => $companyName,
            'scopeDisplayName' => $scopeDisplayName,
            'reportLocationLines' => $reportLocationLines,
            'reportDate' => $reportDate,
            'reportingPeriodLabel' => $reportingPeriodLabel,
            'wasteStreamTotals' => $wasteStreamTotals,
            'classificationTotals' => $classificationTotals,
            'environmentalImpact' => $environmentalImpact,
            'grades' => $grades,
            'recyclingCommodities' => $recyclingCommodities,
            'recyclingCommodities2' => $recyclingCommodities2,
            'summary' => [
                'recyclingRecovered' => $recyclingRecovered,
                'organicsRecovered' => $organicsRecovered,
                'totalIncomingWaste' => $totalIncomingWaste,
                'divertedFromLandfill' => $divertedFromLandfill,
                'landfillSpaceSaved' => $landfillSpaceSaved,
                'lifecycleSaving' => $materialsCO2eTotals['lifecycleSaving'],
            ],
            'landfillSpaceSavedBreakdown' => $landfillSpaceSavedData,
            'materialsCO2e' => $materialsCO2e,
            'materialsCO2eTotals' => $materialsCO2eTotals,
            'carbonEmissionsAvoided' => $this->calculateCarbonEmissionsAvoided($materialsCO2eTotals),
            'cumulativeImpact' => $this->calculateCumulativeImpact($environmentalImpact, $materialsCO2eTotals),
            'recyclingBreakdown' => $this->calculateRecyclingBreakdown($company, $branch, $site, $month, $year, $organicsRecovered, $recyclingRecovered),
        ];
    }

    /**
     * Material-level weights for the calendar month from finalized order waste streams (single source of truth with dashboard).
     *
     * @return \Illuminate\Support\Collection<int, object{material_id: int, total_weight: float, material: \App\Models\Material}>
     */
    private function getMaterialSummaries(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null)
    {
        if ((! $company && ! $branch && ! $site) || ! $month || ! $year) {
            return collect([]);
        }

        $start = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $end = Carbon::createFromDate($year, $month, $lastDay)->format('Y-m-d');

        return $this->orderWasteStreamReporting->materialWeightAggregatesForDateRange(
            $company,
            $branch,
            $site,
            $start,
            $end
        );
    }

    /**
     * Build order query filter based on company, branch, and site
     */
    private function buildOrderFilter($query, ?Company $company, ?Branch $branch, ?Site $site, $startDate, $endDate)
    {
        $query->where('status', 'finalized')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('actual_collection_date', [$startDate, $endDate])
                    ->orWhere(function ($subQ) use ($startDate, $endDate) {
                        $subQ->whereNull('actual_collection_date')
                            ->whereBetween('requested_collection_date', [$startDate, $endDate]);
                    });
            });

        // Filter by site (most specific)
        if ($site) {
            $query->where('site_id', $site->id);
        }
        // Filter by branch (if no site selected)
        elseif ($branch) {
            $query->where('branch_id', $branch->id);
        }
        // Filter by company (if no branch or site selected)
        elseif ($company) {
            $query->where('company_id', $company->id);
        }
    }

    /**
     * Get grades (generalWaste, nonCompactableWaste, hazardousWaste, organicsRecovered) from pre-calculated summaries
     */
    private function getGrades(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null): array
    {
        if ((! $company && ! $branch && ! $site) || ! $month || ! $year) {
            return [
                'generalWaste' => 0,
                'nonCompactableWaste' => 0,
                'hazardousWaste' => 0,
                'organicsRecovered' => 0,
            ];
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        $grades = [
            'generalWaste' => 0,
            'nonCompactableWaste' => 0,
            'hazardousWaste' => 0,
            'organicsRecovered' => 0,
        ];

        foreach ($summaries as $summary) {
            if (! $summary->material || ! $summary->material->wasteStream || ! $summary->material->grade) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            // General Waste: Waste stream = "Waste", Grade = "General Waste"
            if ($wasteStreamName === 'Waste' && $gradeName === 'General Waste') {
                $grades['generalWaste'] += $weight;
            }
            // Non Compactable Waste: Waste stream = "Waste", Grade = "Non Compactable Waste"
            elseif ($wasteStreamName === 'Waste' && $gradeName === 'Non Compactable Waste') {
                $grades['nonCompactableWaste'] += $weight;
            }
            // Hazardous Waste: Any material with Waste stream = "Hazardous Waste"
            elseif ($wasteStreamName === 'Hazardous Waste') {
                $grades['hazardousWaste'] += $weight;
            }
            // Organics Recovered: Waste stream = "Organic Waste", Grade = "Organics Recovered"
            elseif ($wasteStreamName === 'Organic Waste' && $gradeName === 'Organics Recovered') {
                $grades['organicsRecovered'] += $weight;
            }
        }

        // Round to 2 decimal places
        return [
            'generalWaste' => round($grades['generalWaste'], 2),
            'nonCompactableWaste' => round($grades['nonCompactableWaste'], 2),
            'hazardousWaste' => round($grades['hazardousWaste'], 2),
            'organicsRecovered' => round($grades['organicsRecovered'], 2),
        ];
    }

    /**
     * Get material weights from pre-calculated summaries for a company/branch/site in a given month
     */
    private function getMaterialWeights(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null): array
    {
        if ((! $company && ! $branch && ! $site) || ! $month || ! $year) {
            return [];
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        // Group by grade name and sum total_weight
        $materialWeights = [];
        foreach ($summaries as $summary) {
            if (! $summary->material || ! $summary->material->grade) {
                continue;
            }

            $gradeName = $summary->material->grade->name;
            if (! isset($materialWeights[$gradeName])) {
                $materialWeights[$gradeName] = 0;
            }
            $materialWeights[$gradeName] += (float) $summary->total_weight;
        }

        return $materialWeights;
    }

    /**
     * Get recycling commodities array with actual weights
     */
    private function getRecyclingCommodities(array $materialWeights, int $set = 1): array
    {
        $commodities1 = [
            'Alu Cans', 'Alu Foil', 'BOPP', 'CMW', 'CMW Rolls', 'EPS/XPS', 'FN/SBM', 'Glass', 'Hangers',
            'HD', 'HD - Colour', 'HD - PP', 'HD Clear', 'HD Crates', 'HD Dark', 'HD Light', 'HD White',
            'Heavy Steel', 'HL 1', 'HL Books', 'HL Dirty',
        ];

        $commodities2 = [
            'K4', 'K4 Rolls', 'Label Backing', 'LD Clear', 'LD Consul', 'LD Mix', 'Light Steel',
            'Light Steel Cans', 'Light Steel Drums', 'Mixed Bag', 'Oil', 'PET Clear', 'PET Mix',
            'Pet Strapping', 'PP', 'PP Caps', 'Tetrapak', 'Tissue Paper', 'Wrapping', 'XPS', '',
        ];

        $commodities = $set === 1 ? $commodities1 : $commodities2;
        $result = [];

        foreach ($commodities as $name) {
            $weight = isset($materialWeights[$name]) ? round($materialWeights[$name], 2) : 0;
            $result[] = ['name' => $name, 'qty' => $weight];
        }

        return $result;
    }

    /**
     * Calculate landfill space saved breakdown from pre-calculated summaries
     */
    private function getLandfillSpaceSaved(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null, float $organicsRecovered = 0): array
    {
        $zeroWeights = [
            'paper' => 0.0,
            'plastics' => 0.0,
            'aluminium' => 0.0,
            'steel' => 0.0,
            'glass' => 0.0,
            'tetrapak' => 0.0,
            'organics' => 0.0,
        ];

        if ((! $company && ! $branch && ! $site) || ! $month || ! $year) {
            return $this->landfillSpaceCalculator->calculate($zeroWeights);
        }

        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        $weightsKg = [
            'paper' => 0.0,
            'plastics' => 0.0,
            'aluminium' => 0.0,
            'steel' => 0.0,
            'glass' => 0.0,
            'tetrapak' => 0.0,
            'organics' => $organicsRecovered,
        ];

        foreach ($summaries as $summary) {
            if (! $summary->material || ! $summary->material->grade || ! $summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            if ($gradeName === 'Tetrapak') {
                $weightsKg['tetrapak'] += $weight;
            } elseif ($wasteStreamName === 'Plastic') {
                $weightsKg['plastics'] += $weight;
            } elseif ($wasteStreamName === 'Paper') {
                $weightsKg['paper'] += $weight;
            } elseif ($wasteStreamName === 'Aluminium') {
                $weightsKg['aluminium'] += $weight;
            } elseif ($wasteStreamName === 'Metal' && in_array($gradeName, ['Heavy Steel', 'Light Steel', 'Light Steel Cans', 'Light Steel Drums'], true)) {
                $weightsKg['steel'] += $weight;
            } elseif ($wasteStreamName === 'Glass') {
                $weightsKg['glass'] += $weight;
            }
        }

        return $this->landfillSpaceCalculator->calculate($weightsKg);
    }

    /**
     * Calculate materials CO2e with weights and emission factors from pre-calculated summaries
     */
    private function getMaterialsCO2e(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null, float $organicsRecovered = 0): array
    {
        if ((! $company && ! $branch && ! $site) || ! $month || ! $year) {
            return $this->getEmptyMaterialsCO2e();
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        // Initialize totals
        $weights = [
            'paper' => 0,
            'plasticPPHD' => 0,
            'plasticPS' => 0,
            'plasticLDPE' => 0,
            'aluminium' => 0,
            'steel' => 0,
            'glass' => 0,
            'foodWaste' => $organicsRecovered, // Use organics recovered for food waste
            'gardenWaste' => 0,
            'batteries' => 0,
            'electronics' => 0,
            'tetrapak' => 0,
            'wood' => 0,
        ];

        foreach ($summaries as $summary) {
            if (! $summary->material || ! $summary->material->grade || ! $summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            // Tetrapak: grade name = "Tetrapak" (separate from paper)
            if ($gradeName === 'Tetrapak') {
                $weights['tetrapak'] += $weight;
            }
            // Paper: waste stream = "Paper" (excluding Tetrapak)
            elseif ($wasteStreamName === 'Paper' && $gradeName !== 'Tetrapak') {
                $weights['paper'] += $weight;
            }
            // Plastic PP / HD: HD grades and PP grades
            elseif ($wasteStreamName === 'Plastic' && (
                strpos($gradeName, 'HD') === 0 ||
                $gradeName === 'PP' ||
                $gradeName === 'PP Caps'
            )) {
                $weights['plasticPPHD'] += $weight;
            }
            // Plastic PS (Polystyrene): EPS/XPS
            elseif ($wasteStreamName === 'Plastic' && ($gradeName === 'EPS/XPS' || $gradeName === 'XPS')) {
                $weights['plasticPS'] += $weight;
            }
            // Plastic LDPE Film: LD grades
            elseif ($wasteStreamName === 'Plastic' && strpos($gradeName, 'LD') === 0) {
                $weights['plasticLDPE'] += $weight;
            }
            // Aluminium: waste stream = "Aluminium"
            elseif ($wasteStreamName === 'Aluminium') {
                $weights['aluminium'] += $weight;
            }
            // Steel: waste stream = "Metal" with steel grades
            elseif ($wasteStreamName === 'Metal' && (
                $gradeName === 'Heavy Steel' ||
                $gradeName === 'Light Steel' ||
                $gradeName === 'Light Steel Cans' ||
                $gradeName === 'Light Steel Drums'
            )) {
                $weights['steel'] += $weight;
            }
            // Glass: waste stream = "Glass"
            elseif ($wasteStreamName === 'Glass') {
                $weights['glass'] += $weight;
            }
            // Garden Waste: waste stream = "Garden Waste"
            elseif ($wasteStreamName === 'Garden Waste') {
                $weights['gardenWaste'] += $weight;
            }
            // Batteries: waste stream = "Batteries" (if it exists)
            elseif ($wasteStreamName === 'Batteries') {
                $weights['batteries'] += $weight;
            }
            // Electronics (E-waste): waste stream = "Electronics" or "E-waste" (if it exists)
            elseif (in_array($wasteStreamName, ['Electronics', 'E-waste', 'Electronics (E-waste)'])) {
                $weights['electronics'] += $weight;
            }
            // Wood (Timber / Pallets): waste stream = "Wood"
            elseif ($wasteStreamName === 'Wood') {
                $weights['wood'] += $weight;
            }
        }

        $carbonData = $this->carbonCalculator->calculateMaterialsCO2e($weights);

        return [
            'materials' => $carbonData['materials'],
            'totals' => $carbonData['totals'],
        ];
    }

    /**
     * Get empty materials CO2e array for when no data is available
     */
    private function getEmptyMaterialsCO2e(): array
    {
        $materials = [
            ['material' => 'Paper', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Plastic PP / HD', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Plastic PS (Polystyrene)', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Plastic LDPE Film', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Aluminium', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Steel', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Glass', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Food Waste', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Garden Waste', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Batteries', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Electronics (E-waste)', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Tetrapak variants', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Wood (Timber / Pallets)', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
        ];

        return [
            'materials' => $materials,
            'totals' => [
                'scope3EF' => 0,
                'landfillAvoidanceEF' => 0,
                'lifecycleSaving' => 0,
            ],
        ];
    }

    /**
     * Calculate environmental impact (trees saved, energy saved, water saved) using shared WasteImpactCalculator.
     */
    private function getEnvironmentalImpact(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null, float $organicsRecovered = 0): array
    {
        if ((! $company && ! $branch && ! $site) || ! $month || ! $year) {
            return [
                'treesSaved' => 0,
                'energySaved' => 0,
                'waterSaved' => 0,
                'co2Saved' => 0,
                'electricityEquivalentKwhSaGrid' => 0,
                'transportEquivalentKm' => 0,
                'fuelEquivalentLitresPetrol' => 0,
                'carsOffRoadAnnualEquivalent' => 0,
            ];
        }

        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);
        $categoryWeights = $this->wasteImpactCalculator->buildCategoryWeightsFromSummaries($summaries, $organicsRecovered);
        $impact = $this->wasteImpactCalculator->calculateImpactFromCategoryWeights($categoryWeights);

        return [
            'treesSaved' => $impact['treesSaved'],
            'energySaved' => $impact['energySaved'],
            'waterSaved' => $impact['waterSaved'],
            'co2Saved' => $impact['co2Saved'],
            'electricityEquivalentKwhSaGrid' => $impact['electricityEquivalentKwhSaGrid'],
            'transportEquivalentKm' => $impact['transportEquivalentKm'],
            'fuelEquivalentLitresPetrol' => $impact['fuelEquivalentLitresPetrol'],
            'carsOffRoadAnnualEquivalent' => $impact['carsOffRoadAnnualEquivalent'],
        ];
    }

    /**
     * Transport equivalent (km avoided) from lifecycle saving — docs/Dashboard & Reports - Metrics (1).docx
     */
    private function calculateCarbonEmissionsAvoided(array $materialsCO2eTotals): float
    {
        $lifecycleSaving = (float) ($materialsCO2eTotals['lifecycleSaving'] ?? 0);
        if ($lifecycleSaving <= 0) {
            return 0.0;
        }

        return round($lifecycleSaving / 0.192, 2);
    }

    /**
     * Calculate cumulative impact percentages for dashboard
     */
    private function calculateCumulativeImpact(array $environmentalImpact, array $materialsCO2eTotals): array
    {
        $waterSaved = (float) ($environmentalImpact['waterSaved'] ?? 0);
        $electricityKwh = (float) ($environmentalImpact['electricityEquivalentKwhSaGrid'] ?? 0);
        $lifecycleSaving = (float) ($materialsCO2eTotals['lifecycleSaving'] ?? 0);

        return [
            ['name' => 'Water Saved (kL)', 'value' => round($waterSaved, 2), 'color' => '#3b82f6'],
            ['name' => 'Electricity Equivalent (kWh – SA Grid)', 'value' => round($electricityKwh, 2), 'color' => '#eab308'],
            ['name' => 'Total Lifecycle Carbon Avoided (kg CO₂e)', 'value' => round($lifecycleSaving, 2), 'color' => '#6b7280'],
        ];
    }

    /**
     * Calculate recycling breakdown percentages from pre-calculated summaries
     */
    private function calculateRecyclingBreakdown(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null, float $organicsRecovered = 0, float $recyclingRecovered = 0): array
    {
        if ((! $company && ! $branch && ! $site) || ! $month || ! $year || $recyclingRecovered == 0) {
            return [
                ['name' => 'Paper', 'value' => 0, 'color' => '#60a5fa'],
                ['name' => 'Plastics', 'value' => 0, 'color' => '#a3e635'],
                ['name' => 'Aluminium', 'value' => 0, 'color' => '#4b5563'],
                ['name' => 'Organics', 'value' => 0, 'color' => '#fbbf24'],
                ['name' => 'Tetrapak', 'value' => 0, 'color' => '#fde047'],
                ['name' => 'Steel', 'value' => 0, 'color' => '#e5e7eb'],
                ['name' => 'Glass', 'value' => 0, 'color' => '#3b82f6'],
                ['name' => 'Wood', 'value' => 0, 'color' => '#A0522D'],
            ];
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        // Initialize category weights
        $categoryWeights = [
            'paper' => 0,
            'plastics' => 0,
            'aluminium' => 0,
            'organics' => $organicsRecovered,
            'tetrapak' => 0,
            'steel' => 0,
            'glass' => 0,
            'wood' => 0,
        ];

        foreach ($summaries as $summary) {
            if (! $summary->material || ! $summary->material->grade || ! $summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            // Tetrapak: grade name = "Tetrapak" (separate from paper)
            if ($gradeName === 'Tetrapak') {
                $categoryWeights['tetrapak'] += $weight;
            }
            // Paper: waste stream = "Paper" (excluding Tetrapak)
            elseif ($wasteStreamName === 'Paper' && $gradeName !== 'Tetrapak') {
                $categoryWeights['paper'] += $weight;
            }
            // Plastics: waste stream = "Plastic"
            elseif ($wasteStreamName === 'Plastic') {
                $categoryWeights['plastics'] += $weight;
            }
            // Aluminium: waste stream = "Aluminium"
            elseif ($wasteStreamName === 'Aluminium') {
                $categoryWeights['aluminium'] += $weight;
            }
            // Steel: waste stream = "Metal" with steel grades
            elseif ($wasteStreamName === 'Metal' && (
                $gradeName === 'Heavy Steel' ||
                $gradeName === 'Light Steel' ||
                $gradeName === 'Light Steel Cans' ||
                $gradeName === 'Light Steel Drums'
            )) {
                $categoryWeights['steel'] += $weight;
            }
            // Glass: waste stream = "Glass"
            elseif ($wasteStreamName === 'Glass') {
                $categoryWeights['glass'] += $weight;
            }
            // Wood: waste stream = "Wood"
            elseif ($wasteStreamName === 'Wood') {
                $categoryWeights['wood'] += $weight;
            }
        }

        // Calculate total for percentage calculation
        $totalWeight = array_sum($categoryWeights);

        // Calculate percentages
        $breakdown = [];
        if ($totalWeight > 0) {
            $breakdown = [
                ['name' => 'Paper', 'value' => round(($categoryWeights['paper'] / $totalWeight) * 100, 2), 'color' => '#60a5fa'],
                ['name' => 'Plastics', 'value' => round(($categoryWeights['plastics'] / $totalWeight) * 100, 2), 'color' => '#a3e635'],
                ['name' => 'Aluminium', 'value' => round(($categoryWeights['aluminium'] / $totalWeight) * 100, 2), 'color' => '#4b5563'],
                ['name' => 'Organics', 'value' => round(($categoryWeights['organics'] / $totalWeight) * 100, 2), 'color' => '#fbbf24'],
                ['name' => 'Tetrapak', 'value' => round(($categoryWeights['tetrapak'] / $totalWeight) * 100, 2), 'color' => '#fde047'],
                ['name' => 'Steel', 'value' => round(($categoryWeights['steel'] / $totalWeight) * 100, 2), 'color' => '#e5e7eb'],
                ['name' => 'Glass', 'value' => round(($categoryWeights['glass'] / $totalWeight) * 100, 2), 'color' => '#3b82f6'],
                ['name' => 'Wood', 'value' => round(($categoryWeights['wood'] / $totalWeight) * 100, 2), 'color' => '#A0522D'],
            ];
        } else {
            $breakdown = [
                ['name' => 'Paper', 'value' => 0, 'color' => '#60a5fa'],
                ['name' => 'Plastics', 'value' => 0, 'color' => '#a3e635'],
                ['name' => 'Aluminium', 'value' => 0, 'color' => '#4b5563'],
                ['name' => 'Organics', 'value' => 0, 'color' => '#fbbf24'],
                ['name' => 'Tetrapak', 'value' => 0, 'color' => '#fde047'],
                ['name' => 'Steel', 'value' => 0, 'color' => '#e5e7eb'],
                ['name' => 'Glass', 'value' => 0, 'color' => '#3b82f6'],
                ['name' => 'Wood', 'value' => 0, 'color' => '#A0522D'],
            ];
        }

        return $breakdown;
    }

    /**
     * Generate all charts for the report
     */
    private function generateCharts(array $reportData): array
    {
        $timestamp = now()->format('YmdHis');
        $chartPaths = [];

        if (! Storage::disk('public')->exists('charts')) {
            Storage::disk('public')->makeDirectory('charts');
        }

        $wasteStreamRows = array_values(array_filter(
            $reportData['wasteStreamTotals'] ?? [],
            static fn (array $row): bool => ($row['value'] ?? 0) > 0
        ));

        if ($wasteStreamRows === []) {
            $wasteStreamRows = [
                ['name' => 'No data', 'value' => 1.0, 'color' => '#e5e7eb'],
            ];
        }

        // Doughnut with no cutout renders as a pie; QuickChart pie charts may still draw a top legend.
        $chartPaths['page1_waste_stream_pie'] = $this->chartService->generateDoughnutChart([
            'title' => '',
            'data' => array_column($wasteStreamRows, 'value'),
            'colors' => array_column($wasteStreamRows, 'color'),
            'cutout' => '0%',
            'width' => 520,
            'height' => 320,
            'options' => [
                'layout' => [
                    'padding' => 4,
                ],
                'plugins' => [
                    'legend' => [
                        'display' => false,
                    ],
                    'title' => [
                        'display' => false,
                    ],
                    'tooltip' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ], "page1_waste_stream_pie_{$timestamp}.png");

        $classification = $reportData['classificationTotals'] ?? [];
        $donutDefs = [
            'page1_donut_avoidance' => ['label' => 'AVOIDANCE', 'pct' => (float) ($classification['avoidance']['percentage'] ?? 0), 'color' => '#BDBDBD'],
            'page1_donut_recycling' => ['label' => 'RECYCLING', 'pct' => (float) ($classification['recycling']['percentage'] ?? 0), 'color' => '#6FCF97'],
            'page1_donut_recovery' => ['label' => 'RECOVERY', 'pct' => (float) ($classification['recovery']['percentage'] ?? 0), 'color' => '#2D9CDB'],
            'page1_donut_disposal' => ['label' => 'DISPOSAL', 'pct' => (float) ($classification['disposal']['percentage'] ?? 0), 'color' => '#1C1C1C'],
            'page1_donut_diverted' => ['label' => 'DIVERTED', 'pct' => (float) ($classification['diverted']['percentage'] ?? 0), 'color' => '#C69200'],
        ];

        foreach ($donutDefs as $key => $def) {
            $p = min(100.0, max(0.0, $def['pct']));
            $rest = $p >= 100.0 ? 0.0 : max(0.01, 100.0 - $p);
            $chartPaths[$key] = $this->chartService->generateDoughnutChart([
                'title' => '',
                'labels' => ['', ''],
                'data' => $p <= 0 && $rest >= 100 ? [0.0, 100.0] : [$p, $rest],
                'colors' => [$def['color'], '#e5e7eb'],
                'legendPosition' => 'bottom',
                'cutout' => '56%',
                'width' => 260,
                'height' => 260,
                'options' => [
                    'plugins' => [
                        'legend' => ['display' => false],
                        'title' => ['display' => false],
                        'tooltip' => [
                            'enabled' => false,
                        ],
                    ],
                ],
            ], "{$key}_{$timestamp}.png");
        }

        return $chartPaths;
    }
}
