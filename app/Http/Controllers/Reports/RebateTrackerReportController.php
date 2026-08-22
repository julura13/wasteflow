<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateRebateTrackerPdfJob;
use App\Models\RebateReportExport;
use App\Services\CompanyUserService;
use App\Services\RebateTrackerReportService;
use App\Traits\ScopeByClientTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RebateTrackerReportController extends Controller
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

        $rebateData = $this->rebateTrackerReportService->getRebateTrackerData($startDate, $endDate, $companyId, $branchId, $siteId, $user, $companyIds);

        $companies = $this->scopeCompaniesForUser();

        // Internal-only: which service provider handled a load. Drives both the "Rebate by
        // Service Provider" breakdown and the Provider column in the main table below.
        $canViewProvider = $user->can('view-reports-all');

        return Inertia::render('Reports/RebateTracker', [
            'rebateData' => $rebateData,
            'canViewProvider' => $canViewProvider,
            'providerBreakdown' => $canViewProvider ? $this->rebateTrackerReportService->providerBreakdown($rebateData) : [],
            'companies' => $companies,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
            ],
            'totalRebate' => $rebateData->sum('total'),
            'totalWeight' => $rebateData->sum('weight'),
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
        $filename = 'Rebate_Tracker_'.$validated['start_date'].'_to_'.$validated['end_date'].'.pdf';

        $export = RebateReportExport::query()->create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'status' => RebateReportExport::STATUS_PENDING,
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

        GenerateRebateTrackerPdfJob::dispatch($export->id)->afterResponse();

        return back()->with('success', 'Your PDF report is being prepared. You can download it when it is ready using the button below.')
            ->with('rebate_pdf_export_uuid', $uuid);
    }

    public function pdfStatus(Request $request, string $uuid)
    {
        $export = RebateReportExport::query()
            ->where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status' => $export->status,
            'download_url' => $export->status === RebateReportExport::STATUS_COMPLETED
                ? route('reports.rebate-tracker-pdf.download', ['uuid' => $uuid])
                : null,
            'error_message' => $export->error_message,
        ]);
    }

    public function downloadPdf(Request $request, string $uuid)
    {
        $export = RebateReportExport::query()
            ->where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($export->status !== RebateReportExport::STATUS_COMPLETED) {
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
}
