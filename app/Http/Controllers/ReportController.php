<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Reports\CustomerOrderFrequencyReportController;
use App\Http\Controllers\Reports\EnvironmentalCalculatorController;
use App\Http\Controllers\Reports\ManagementReportController;
use App\Jobs\GenerateWasteManagementPdfJob;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Material;
use App\Models\RecoveryRatingTier;
use App\Models\Site;
use App\Models\User;
use App\Models\WasteManagementReportExport;
use App\Services\CarbonCalculator;
use App\Services\CustomerOrderFrequencyReportService;
use App\Services\LandfillSpaceCalculator;
use App\Services\LifecycleCarbonEquivalency;
use App\Services\ManagementReportService;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    use ScopeByClientTrait;

    protected WasteImpactCalculator $wasteImpactCalculator;

    protected CarbonCalculator $carbonCalculator;

    protected LandfillSpaceCalculator $landfillSpaceCalculator;

    protected WaterCalculator $waterCalculator;

    protected LifecycleCarbonEquivalency $lifecycleCarbonEquivalency;

    protected OrderWasteStreamReportingService $orderWasteStreamReporting;

    protected CustomerOrderFrequencyReportService $customerOrderFrequencyReport;

    protected WasteManagementReportPdfGenerator $wasteManagementReportPdfGenerator;

    protected ManagementReportService $managementReport;

    public function __construct(
        WasteImpactCalculator $wasteImpactCalculator,
        CarbonCalculator $carbonCalculator,
        LandfillSpaceCalculator $landfillSpaceCalculator,
        WaterCalculator $waterCalculator,
        LifecycleCarbonEquivalency $lifecycleCarbonEquivalency,
        OrderWasteStreamReportingService $orderWasteStreamReporting,
        CustomerOrderFrequencyReportService $customerOrderFrequencyReport,
        WasteManagementReportPdfGenerator $wasteManagementReportPdfGenerator,
        ManagementReportService $managementReport,
    ) {
        $this->wasteImpactCalculator = $wasteImpactCalculator;
        $this->carbonCalculator = $carbonCalculator;
        $this->landfillSpaceCalculator = $landfillSpaceCalculator;
        $this->waterCalculator = $waterCalculator;
        $this->lifecycleCarbonEquivalency = $lifecycleCarbonEquivalency;
        $this->orderWasteStreamReporting = $orderWasteStreamReporting;
        $this->customerOrderFrequencyReport = $customerOrderFrequencyReport;
        $this->wasteManagementReportPdfGenerator = $wasteManagementReportPdfGenerator;
        $this->managementReport = $managementReport;
    }

    public function index(): Response
    {
        return Inertia::render('Reports/Index');
    }

    // =========================================================================
    // Customer Order Frequency & Management Reports (Delegated to sub-controllers)
    // =========================================================================

    public function customerOrderFrequencies(Request $request)
    {
        return app(CustomerOrderFrequencyReportController::class)->index($request);
    }

    public function customerOrderFrequenciesExport(Request $request)
    {
        return app(CustomerOrderFrequencyReportController::class)->export($request);
    }

    public function customerOrderFrequenciesExportPdf(Request $request)
    {
        return app(CustomerOrderFrequencyReportController::class)->exportPdf($request);
    }

    public function managementReport(Request $request)
    {
        return app(ManagementReportController::class)->index($request);
    }

    public function managementReportExport(Request $request)
    {
        return app(ManagementReportController::class)->export($request);
    }

    public function managementReportExportPdf(Request $request)
    {
        return app(ManagementReportController::class)->exportPdf($request);
    }

    /**
     * Waste management report filter form (PDF is generated asynchronously).
     */
    public function wasteManagement(Request $request)
    {
        $companies = $this->scopeCompaniesForUser();
        $month = (int) ($request->input('month') ?? date('m'));
        $year = (int) ($request->input('year') ?? date('Y'));

        return Inertia::render('Reports/WasteManagement', [
            'companies' => $companies,
            'filters' => [
                'company_id' => $request->input('company_id') ?? '',
                'branch_id' => $request->input('branch_id') ?? '',
                'site_id' => $request->input('site_id') ?? '',
                'month' => $month,
                'year' => $year,
                'to_month' => (int) ($request->input('to_month') ?? $month),
                'to_year' => (int) ($request->input('to_year') ?? $year),
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
            'to_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'to_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $companyId = (int) $validated['company_id'];
        $branchId = isset($validated['branch_id']) && $validated['branch_id'] !== null ? (int) $validated['branch_id'] : null;
        $siteId = isset($validated['site_id']) && $validated['site_id'] !== null ? (int) $validated['site_id'] : null;
        $month = (int) $validated['month'];
        $year = (int) $validated['year'];
        $toMonth = (int) ($validated['to_month'] ?? $month);
        $toYear = (int) ($validated['to_year'] ?? $year);

        if (($toYear * 12 + $toMonth) < ($year * 12 + $month)) {
            throw ValidationException::withMessages([
                'to_month' => 'The end of the reporting period must not be before the start.',
            ]);
        }

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $uuid = (string) Str::uuid();
        $filename = $month === $toMonth && $year === $toYear
            ? sprintf('WasteFlow_Resource_Intelligence_Report_%d-%02d.pdf', $year, $month)
            : sprintf('WasteFlow_Resource_Intelligence_Report_%d-%02d_to_%d-%02d.pdf', $year, $month, $toYear, $toMonth);

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
                'month' => $month,
                'year' => $year,
                'to_month' => $toMonth,
                'to_year' => $toYear,
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
            ->where([
                'uuid' => $uuid,
                'user_id' => $request->user()->id,
            ])
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
            ->where([
                'uuid' => $uuid,
                'user_id' => $request->user()->id,
            ])
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
     * Single-page "Certificate of Achievement" for one client/month, showing their Resource
     * Recovery Rating tier and headline stats. Small and fast enough to render with Dompdf
     * synchronously - no queue/export-table round trip needed like the multi-page report.
     */
    public function downloadClientMonthlyCertificate(Request $request)
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $companyId = (int) $validated['company_id'];
        $month = (int) $validated['month'];
        $year = (int) $validated['year'];

        [$companyId] = $this->enforceCompanyScope($companyId, null, null);

        $company = Company::findOrFail($companyId);
        $reportData = $this->getReportData($company, null, null, $month, $year, $month, $year);

        $divertedFromLandfillPercentage = (float) $reportData['summary']['divertedFromLandfill'];
        $periodEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $tier = RecoveryRatingTier::forPercentage($divertedFromLandfillPercentage);

        $companyNameUpper = Str::upper($reportData['companyName']);
        $percentageDisplay = number_format($divertedFromLandfillPercentage, 1);
        $monthYearUpper = Str::upper($periodEnd->format('F Y'));
        $completeDateUpper = Str::upper($periodEnd->format('d F Y'));
        $tierNameUpper = $tier ? Str::upper($tier->name) : null;

        $summaryText = $tierNameUpper
            ? "A DIVERSION OF {$percentageDisplay}% WAS ACHIEVED FOR {$monthYearUpper}, EARNING A {$tierNameUpper} RESOURCE RECOVERY RATING\u{2122}, DEMONSTRATING {$companyNameUpper}\u{2019}S CONTINUED WASTE DIVERSION SUCCESS"
            : "A DIVERSION OF {$percentageDisplay}% WAS ACHIEVED FOR THE MONTH OF {$monthYearUpper} DEMONSTRATING THE CONTINUED SUCCESS OF {$companyNameUpper}\u{2019}S WASTE DIVERSION PROGRAM";

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $summarySizing = $this->certificateSummaryFontSize($summaryText);

        $dompdf = new Dompdf($options);
        $html = view('reports.client-monthly-certificate-pdf', [
            'companyNameUpper' => $companyNameUpper,
            'companyNameFontSize' => $this->certificateCompanyNameFontSize($companyNameUpper),
            'percentageDisplay' => $percentageDisplay,
            'monthYearUpper' => $monthYearUpper,
            'completeDateUpper' => $completeDateUpper,
            'dateFontSize' => $this->certificateDateFontSize($completeDateUpper),
            'tierNameUpper' => $tierNameUpper,
            'tierColor' => $tier?->color,
            'summaryFontSize' => $summarySizing['size'],
            'summaryLineHeight' => $summarySizing['lineHeight'],
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        $filename = sprintf('WasteFlow_Certificate_%s_%04d-%02d.pdf', Str::slug($reportData['companyName']), $year, $month);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Font size (pt) for the certificate's company-name field, shrunk so the name always
     * renders on a single line within the fixed-width field on the certificate background.
     *
     * Character-count buckets used to drive this (e.g. "<=20 chars => 30pt") but character
     * count doesn't track rendered width - "DEVONBOSCH ESTATE" (17 chars) measures wider at
     * 30pt than "WESKUS MALL" (11 chars) because of the actual glyph widths involved, so it
     * wrapped onto a second line and collided with the summary text below it. Measuring the
     * real font's metrics instead of guessing from length fixes this for every name, not just
     * the ones we happened to test.
     */
    private function certificateCompanyNameFontSize(string $companyNameUpper): float
    {
        return $this->fontSizeToFitOneLine(
            $companyNameUpper,
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSerif-Bold.ttf'),
            maxWidthMm: 145.0,
            maxSize: 30.0,
            minSize: 12.0,
        );
    }

    /**
     * Font size/line-height (pt) for the certificate's summary sentence, scaled down as the
     * assembled sentence grows (longer company/tier names) so it stays within the fixed-height
     * field above the signature line instead of overflowing onto it.
     *
     * @return array{size: float, lineHeight: float}
     */
    private function certificateSummaryFontSize(string $summaryText): array
    {
        return match (true) {
            strlen($summaryText) <= 150 => ['size' => 13.5, 'lineHeight' => 1.5],
            strlen($summaryText) <= 180 => ['size' => 11.5, 'lineHeight' => 1.35],
            strlen($summaryText) <= 210 => ['size' => 9.5, 'lineHeight' => 1.25],
            default => ['size' => 8.0, 'lineHeight' => 1.2],
        };
    }

    /**
     * Font size (pt) for the certificate's date field, shrunk so the date always renders on a
     * single line within the fixed-width field and never wraps onto the "Date" label printed
     * on the certificate background below it (see {@see certificateCompanyNameFontSize} for
     * why this measures the real font's metrics instead of guessing from character count).
     */
    private function certificateDateFontSize(string $completeDateUpper): float
    {
        return $this->fontSizeToFitOneLine(
            $completeDateUpper,
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
            maxWidthMm: 34.7,
            maxSize: 12.5,
            minSize: 8.0,
        );
    }

    /**
     * Largest font size (pt), stepping down from $maxSize to $minSize in 0.5pt increments, at
     * which $text renders on a single line no wider than $maxWidthMm when set in $fontFile -
     * measured with that font's real glyph metrics via imagettfbbox() rather than guessed from
     * character count. Falls back to $maxSize if GD/FreeType or the font file isn't available,
     * so the certificate still renders (just without the shrink-to-fit protection). Falls back
     * to $minSize (best-effort, may overflow slightly rather than wrap) if nothing fits.
     */
    private function fontSizeToFitOneLine(string $text, string $fontFile, float $maxWidthMm, float $maxSize, float $minSize): float
    {
        if (! function_exists('imagettfbbox') || ! is_file($fontFile)) {
            return $maxSize;
        }

        $maxWidthPt = $maxWidthMm * 2.83464567;

        for ($size = $maxSize; $size > $minSize; $size -= 0.5) {
            $box = imagettfbbox($size, 0, $fontFile, $text);
            $widthPt = abs($box[2] - $box[0]) * 0.75;

            if ($widthPt <= $maxWidthPt) {
                return $size;
            }
        }

        return $minSize;
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
        $toMonth = (int) ($filters['to_month'] ?? $month);
        $toYear = (int) ($filters['to_year'] ?? $year);

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScopeForUser($user, $companyId ? (int) $companyId : null, $branchId ? (int) $branchId : null, $siteId ? (int) $siteId : null);

        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::with('company')->find($branchId) : null;
        $site = $siteId ? Site::with('branch.company')->find($siteId) : null;

        [$pdfFilename, $binary] = $this->buildWasteManagementPdfBinary($company, $branch, $site, $month, $year, $toMonth, $toYear, $user);

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
     * One-time print-preview page visited by Browsershot (no auth middleware — token is the credential).
     */
    public function resourceIntelligencePrintPreview(string $token): Response
    {
        $payload = cache()->get('ri_pdf_print_'.$token);
        abort_if(! $payload, 404);

        $user = User::find($payload['user_id']);
        abort_if(! $user, 404);

        auth()->login($user);
        cache()->forget('ri_pdf_print_'.$token);

        $filters = $payload['filters'];
        $company = $filters['company_id'] ? Company::find($filters['company_id']) : null;
        $branch = $filters['branch_id'] ? Branch::with('company')->find($filters['branch_id']) : null;
        $site = $filters['site_id'] ? Site::with('branch.company')->find($filters['site_id']) : null;

        $reportData = $this->getReportData(
            $company,
            $branch,
            $site,
            (int) $filters['month'],
            (int) $filters['year'],
            (int) ($filters['to_month'] ?? $filters['month']),
            (int) ($filters['to_year'] ?? $filters['year']),
        );

        return Inertia::render('Reports/ResourceIntelligence', [
            'reportData' => $reportData,
            'companies' => [],
            'filters' => $filters,
            'isPrint' => true,
        ]);
    }

    /**
     * @return array{0: string, 1: string} Filename and raw PDF bytes
     */
    private function buildWasteManagementPdfBinary(?Company $company, ?Branch $branch, ?Site $site, int $month, int $year, int $toMonth, int $toYear, User $user): array
    {
        $token = (string) Str::uuid();
        cache()->put('ri_pdf_print_'.$token, [
            'user_id' => $user->id,
            'filters' => [
                'company_id' => $company?->id,
                'branch_id' => $branch?->id,
                'site_id' => $site?->id,
                'month' => $month,
                'year' => $year,
                'to_month' => $toMonth,
                'to_year' => $toYear,
            ],
        ], now()->addMinutes(10));

        $previewUrl = route('reports.resource-intelligence.print-preview', ['token' => $token]);

        $filename = $month === $toMonth && $year === $toYear
            ? sprintf('WasteFlow_Resource_Intelligence_Report_%04d-%02d.pdf', $year, $month)
            : sprintf('WasteFlow_Resource_Intelligence_Report_%04d-%02d_to_%04d-%02d.pdf', $year, $month, $toYear, $toMonth);

        return [$filename, $this->wasteManagementReportPdfGenerator->generateFromUrl($previewUrl)];
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
        $toMonth = $request->filled('to_month') ? (int) $request->input('to_month') : $month;
        $toYear = $request->filled('to_year') ? (int) $request->input('to_year') : $year;

        // Guard against a hand-edited/stale URL with an inverted range (the UI itself
        // already clamps this before submitting) - fall back to a single-month report
        // for "from" rather than silently rendering an empty one.
        if (($toYear * 12 + $toMonth) < ($year * 12 + $month)) {
            $toMonth = $month;
            $toYear = $year;
        }

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::with('company')->find($branchId) : null;
        $site = $siteId ? Site::with('branch.company')->find($siteId) : null;

        $reportData = $this->getReportData($company, $branch, $site, $month, $year, $toMonth, $toYear);
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
                'to_month' => $toMonth,
                'to_year' => $toYear,
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

        $reportData = $this->getReportData($company, $branch, $site, (int) $month, (int) $year, (int) $month, (int) $year);

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

    // =========================================================================
    // Environmental Calculators (Delegated to EnvironmentalCalculatorController)
    // =========================================================================

    public function carbonCalculator()
    {
        return app(EnvironmentalCalculatorController::class)->carbonCalculator();
    }

    public function carbonCalculatorCalculate(Request $request)
    {
        return app(EnvironmentalCalculatorController::class)->carbonCalculatorCalculate($request);
    }

    public function landfillSpaceCalculator()
    {
        return app(EnvironmentalCalculatorController::class)->landfillSpaceCalculator();
    }

    public function landfillSpaceCalculatorCalculate(Request $request)
    {
        return app(EnvironmentalCalculatorController::class)->landfillSpaceCalculatorCalculate($request);
    }

    public function waterCalculator()
    {
        return app(EnvironmentalCalculatorController::class)->waterCalculator();
    }

    public function waterCalculatorCalculate(Request $request)
    {
        return app(EnvironmentalCalculatorController::class)->waterCalculatorCalculate($request);
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
     * Get report data for waste management report. The reporting period runs from the
     * first day of $fromMonth/$fromYear to the last day of $toMonth/$toYear (inclusive).
     * $toMonth/$toYear default to $fromMonth/$fromYear, giving a single-month report.
     */
    private function getReportData(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $fromMonth = null, ?int $fromYear = null, ?int $toMonth = null, ?int $toYear = null): array
    {
        $toMonth ??= $fromMonth;
        $toYear ??= $fromYear;

        // Determine the company name for display
        $companyName = 'XXXX';
        if ($site && $site->branch && $site->branch->company) {
            $companyName = $site->branch->company->name;
        } elseif ($branch && $branch->company) {
            $companyName = $branch->company->name;
        } elseif ($company) {
            $companyName = $company->name;
        }

        $scopeDisplayNameParts = array_values(array_filter([
            $companyName,
            $branch?->name,
            $site?->name,
        ], static fn (?string $part): bool => $part !== null && $part !== ''));

        $scopeDisplayName = implode(' - ', $scopeDisplayNameParts);

        $reportLocationLines = array_values(array_filter([
            $company ? 'Customer: '.$company->name : null,
            $branch ? 'Branch: '.$branch->name : null,
            $site ? 'Site: '.$site->name : null,
        ], static fn (?string $line): bool => $line !== null && $line !== ''));

        if ($reportLocationLines === []) {
            $reportLocationLines = [
                'No customer selected. Open Reports → WasteFlow Resource Intelligence™ Report, choose a customer and period, then use Download PDF so the link includes your filters.',
            ];
        }

        // Report period label and short date tag (yyyy/mm/dd; first day of $fromMonth for tag)
        $reportDate = 'XXXX';
        $reportingPeriodLabel = 'Reporting period';
        $startDate = null;
        $endDate = null;
        if ($fromMonth && $fromYear && $toMonth && $toYear) {
            $start = Carbon::createFromDate($fromYear, $fromMonth, 1);
            $lastDay = cal_days_in_month(CAL_GREGORIAN, $toMonth, $toYear);
            $end = Carbon::createFromDate($toYear, $toMonth, $lastDay);
            $startDate = $start->format('Y-m-d');
            $endDate = $end->format('Y-m-d');
            $reportDate = $start->format(DisplayDate::CALENDAR);
            $reportingPeriodLabel = sprintf(
                'Reporting period (%s to %s)',
                $start->format(DisplayDate::CALENDAR),
                $end->format(DisplayDate::CALENDAR)
            );
        }

        $materialSummaries = ((! $company && ! $branch && ! $site) || ! $startDate || ! $endDate)
            ? collect()
            : $this->getMaterialSummaries($company, $branch, $site, $startDate, $endDate);

        $wasteStreamTotals = $this->orderWasteStreamReporting->wasteStreamTotalsFromSummaries($materialSummaries);
        $classificationTotals = $this->orderWasteStreamReporting->classificationTotalsFromSummaries($materialSummaries);

        $grades = $this->getGrades($company, $branch, $site, $startDate, $endDate);
        [$recyclingCommodities, $recyclingCommodities2] = $this->getRecyclingCommodities($materialSummaries);

        // Calculate recyclingRecovered = sum of all recycling weights
        $recyclingRecovered = 0;
        foreach ($recyclingCommodities as $commodity) {
            $recyclingRecovered += (float) $commodity['qty'];
        }
        foreach ($recyclingCommodities2 as $commodity) {
            $recyclingRecovered += (float) $commodity['qty'];
        }
        $recyclingRecovered = round($recyclingRecovered, 2);

        // organicsRecovered is kept as organic-waste-only for CO2e, landfill space, and environmental impact calculations
        $organicsRecovered = $grades['organicsRecovered'];

        // materialRecovery = all "Recovered" classified weight minus the organic sub-stream
        $grades['materialRecovery'] = max(0.0, round(($classificationTotals['recovery']['total'] ?? 0) - $organicsRecovered, 2));

        // Use classification totals as the single source of truth for aggregate figures
        $totalIncomingWaste = $classificationTotals['total'];
        $divertedFromLandfill = (float) ($classificationTotals['diverted']['percentage'] ?? 0);

        // Calculate landfill space saved breakdown
        $landfillSpaceSavedData = $this->getLandfillSpaceSaved($company, $branch, $site, $startDate, $endDate, $organicsRecovered);
        $landfillSpaceSaved = $landfillSpaceSavedData['total'];

        // Calculate materials CO2e
        $materialsCO2eData = $this->getMaterialsCO2e($company, $branch, $site, $startDate, $endDate, $organicsRecovered);
        $materialsCO2e = $materialsCO2eData['materials'];
        $materialsCO2eTotals = $materialsCO2eData['totals'];

        // Calculate environmental impact (trees, energy, water, dashboard-style CO₂e + equivalencies)
        $environmentalImpact = $this->getEnvironmentalImpact($company, $branch, $site, $startDate, $endDate, $organicsRecovered);

        // Align all carbon metrics with materials table lifecycle total (split plastics)
        $lifecycleKg = (float) ($materialsCO2eTotals['lifecycleSaving'] ?? 0);
        $reportEquivalency = $this->lifecycleCarbonEquivalency->fromLifecycleSavingKgCo2e($lifecycleKg);
        $environmentalImpact['co2Saved'] = $lifecycleKg;
        $environmentalImpact['electricityEquivalentKwhSaGrid'] = $reportEquivalency['electricityEquivalentKwhSaGrid'];
        $environmentalImpact['transportEquivalentKm'] = $reportEquivalency['transportEquivalentKm'];
        $environmentalImpact['fuelEquivalentLitresPetrol'] = $reportEquivalency['fuelEquivalentLitresPetrol'];
        $environmentalImpact['carsOffRoadAnnualEquivalent'] = $reportEquivalency['carsOffRoadAnnualEquivalent'];

        // Jan-Dec trend data for the monthly report's line graphs (same year as the start of the reporting period).
        $gradeSummaryByYear = $fromYear ? $this->orderWasteStreamReporting->gradeSummaryForYear($company, $branch, $site, $fromYear) : [];
        $wasteManagementTrendByYear = $fromYear ? $this->orderWasteStreamReporting->classificationTotalsByMonthForYear($company, $branch, $site, $fromYear) : [];

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
                'carbonAvoidanceIntensity' => $this->calculateCarbonAvoidanceIntensity($materialsCO2eTotals, $totalIncomingWaste),
            ],
            'landfillSpaceSavedBreakdown' => $landfillSpaceSavedData,
            'materialsCO2e' => $materialsCO2e,
            'materialsCO2eTotals' => $materialsCO2eTotals,
            'carbonEmissionsAvoided' => $this->calculateCarbonEmissionsAvoided($materialsCO2eTotals),
            'cumulativeImpact' => $this->calculateCumulativeImpact($environmentalImpact, $materialsCO2eTotals),
            'recyclingBreakdown' => $this->calculateRecyclingBreakdown($company, $branch, $site, $startDate, $endDate, $organicsRecovered, $recyclingRecovered),
            'gradeSummaryByYear' => $gradeSummaryByYear,
            'wasteManagementTrendByYear' => $wasteManagementTrendByYear,
        ];
    }

    /**
     * Material-level weights for the reporting date range from finalized order waste streams (single source of truth with dashboard).
     *
     * @return Collection<int, object{material_id: int, total_weight: float, material: Material}>
     */
    private function getMaterialSummaries(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?string $startDate = null, ?string $endDate = null)
    {
        if ((! $company && ! $branch && ! $site) || ! $startDate || ! $endDate) {
            return collect([]);
        }

        return $this->orderWasteStreamReporting->materialWeightAggregatesForDateRange(
            $company,
            $branch,
            $site,
            $startDate,
            $endDate
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
    private function getGrades(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?string $startDate = null, ?string $endDate = null): array
    {
        if ((! $company && ! $branch && ! $site) || ! $startDate || ! $endDate) {
            return [
                'generalWaste' => 0,
                'nonCompactableWaste' => 0,
                'hazardousWaste' => 0,
                'organicsRecovered' => 0,
            ];
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $startDate, $endDate);

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
     * Build recycling commodity rows from material summaries, keyed by grade name,
     * filtered to the Recycling classification, sorted alphabetically, and split
     * into two equal halves for the two-column report layout.
     *
     * @param  Collection<int, object{material_id: int, total_weight: float, material: Material}>  $materialSummaries
     * @return array{0: list<array{name: string, qty: float}>, 1: list<array{name: string, qty: float}>}
     */
    private function getRecyclingCommodities(Collection $materialSummaries): array
    {
        $weights = [];

        foreach ($materialSummaries as $summary) {
            if (! $summary->material || ! $summary->material->grade || ! $summary->material->classification) {
                continue;
            }
            if (! in_array($summary->material->classification->slug, ['recycling', 'recovery'], true)) {
                continue;
            }
            $name = $summary->material->grade->name;
            $weights[$name] = ($weights[$name] ?? 0.0) + (float) $summary->total_weight;
        }

        ksort($weights);

        $all = array_map(
            fn ($name, $weight) => ['name' => $name, 'qty' => round($weight, 2)],
            array_keys($weights),
            $weights
        );

        $half = (int) ceil(count($all) / 2);

        return [
            array_slice($all, 0, $half),
            array_slice($all, $half),
        ];
    }

    /**
     * Calculate landfill space saved breakdown from pre-calculated summaries
     */
    private function getLandfillSpaceSaved(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?string $startDate = null, ?string $endDate = null, float $organicsRecovered = 0): array
    {
        $zeroWeights = [
            'paper' => 0.0,
            'plastics' => 0.0,
            'aluminium' => 0.0,
            'steel' => 0.0,
            'glass' => 0.0,
            'tetrapak' => 0.0,
            'organics' => 0.0,
            'wood' => 0.0,
        ];

        if ((! $company && ! $branch && ! $site) || ! $startDate || ! $endDate) {
            return $this->landfillSpaceCalculator->calculate($zeroWeights);
        }

        $summaries = $this->getMaterialSummaries($company, $branch, $site, $startDate, $endDate);
        $weightsKg = $this->wasteImpactCalculator->buildCategoryWeightsFromSummaries($summaries, $organicsRecovered);

        return $this->landfillSpaceCalculator->calculate($weightsKg);
    }

    /**
     * Calculate materials CO2e with weights and emission factors from pre-calculated summaries
     */
    private function getMaterialsCO2e(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?string $startDate = null, ?string $endDate = null, float $organicsRecovered = 0): array
    {
        if ((! $company && ! $branch && ! $site) || ! $startDate || ! $endDate) {
            return $this->getEmptyMaterialsCO2e();
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $startDate, $endDate);

        $weights = $this->wasteImpactCalculator->buildCarbonWeightsFromSummaries($summaries, $organicsRecovered);

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
            ['material' => 'Wood – Reuse (Pallets & Timber)', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'lifecycleSaving' => 0],
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
    private function getEnvironmentalImpact(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?string $startDate = null, ?string $endDate = null, float $organicsRecovered = 0): array
    {
        if ((! $company && ! $branch && ! $site) || ! $startDate || ! $endDate) {
            return [
                'treesSaved' => 0,
                'energySaved' => 0,
                'barrelsOfOilSaved' => 0,
                'homesPoweredOneMonth' => 0,
                'waterSaved' => 0,
                'co2Saved' => 0,
                'electricityEquivalentKwhSaGrid' => 0,
                'transportEquivalentKm' => 0,
                'fuelEquivalentLitresPetrol' => 0,
                'carsOffRoadAnnualEquivalent' => 0,
            ];
        }

        $summaries = $this->getMaterialSummaries($company, $branch, $site, $startDate, $endDate);
        $carbonWeights = $this->wasteImpactCalculator->buildCarbonWeightsFromSummaries($summaries, $organicsRecovered);
        $impact = $this->wasteImpactCalculator->calculateImpactFromCarbonWeights($carbonWeights);

        return [
            'treesSaved' => $impact['treesSaved'],
            'energySaved' => $impact['energySaved'],
            'barrelsOfOilSaved' => $impact['barrelsOfOilSaved'],
            'homesPoweredOneMonth' => $impact['homesPoweredOneMonth'],
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
     * Carbon Avoidance Intensity (CAI): kg CO2e avoided per TON of waste managed - a
     * volume-independent efficiency metric, distinct from the absolute lifecycle saving.
     * $totalIncomingWaste is in kg; converted to tons (÷1000) to match the client's spec.
     */
    private function calculateCarbonAvoidanceIntensity(array $materialsCO2eTotals, float $totalIncomingWaste): float
    {
        $lifecycleSaving = (float) ($materialsCO2eTotals['lifecycleSaving'] ?? 0);
        if ($lifecycleSaving <= 0 || $totalIncomingWaste <= 0) {
            return 0.0;
        }

        $totalIncomingWasteTons = $totalIncomingWaste / 1000;

        return round($lifecycleSaving / $totalIncomingWasteTons, 2);
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
    private function calculateRecyclingBreakdown(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?string $startDate = null, ?string $endDate = null, float $organicsRecovered = 0, float $recyclingRecovered = 0): array
    {
        if ((! $company && ! $branch && ! $site) || ! $startDate || ! $endDate || $recyclingRecovered == 0) {
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
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $startDate, $endDate);
        $categoryWeights = $this->wasteImpactCalculator->buildCategoryWeightsFromSummaries($summaries, $organicsRecovered);

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
}
