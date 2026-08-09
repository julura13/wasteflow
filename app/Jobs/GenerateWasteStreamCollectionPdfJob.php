<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use App\Models\WasteStreamCollectionReportExport;
use App\Services\CompanyUserService;
use App\Services\RebateTrackerReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateWasteStreamCollectionPdfJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(public int $wasteStreamCollectionReportExportId) {}

    public function handle(RebateTrackerReportService $rebateTrackerReportService, CompanyUserService $companyUserService): void
    {
        $export = WasteStreamCollectionReportExport::query()->find($this->wasteStreamCollectionReportExportId);
        if (! $export) {
            return;
        }

        if ($export->expires_at->isPast()) {
            $export->update([
                'status' => WasteStreamCollectionReportExport::STATUS_FAILED,
                'error_message' => 'This export request has expired. Please generate the report again.',
            ]);

            return;
        }

        $export->update(['status' => WasteStreamCollectionReportExport::STATUS_PROCESSING, 'error_message' => null]);

        try {
            $user = User::query()->findOrFail($export->user_id);
            $companyIds = $user->isAdmin() ? [] : $companyUserService->getCompanyIdsForUser($user);

            $filters = $export->filters;
            $startDate = $filters['start_date'];
            $endDate = $filters['end_date'];
            $companyId = $filters['company_id'] ?? null;
            $branchId = $filters['branch_id'] ?? null;
            $siteId = $filters['site_id'] ?? null;

            $wasteStreamBreakdown = $rebateTrackerReportService->getWasteStreamGradeBreakdown(
                $startDate,
                $endDate,
                $companyId,
                $branchId,
                $siteId,
                $user,
                $companyIds,
            );

            $totalWeight = (float) $wasteStreamBreakdown->sum('subtotal_weight');

            $pdfFilters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
                'company_name' => $companyId ? Company::query()->whereKey($companyId)->value('name') : null,
                'branch_name' => $branchId ? Branch::query()->whereKey($branchId)->value('name') : null,
                'site_name' => $siteId ? Site::query()->whereKey($siteId)->value('name') : null,
            ];

            $binary = $rebateTrackerReportService->renderWasteStreamCollectionPdfBinary($wasteStreamBreakdown, $pdfFilters, $totalWeight);

            $relativePath = 'waste-stream-collection-reports/'.$export->uuid.'.pdf';
            Storage::disk($export->disk)->put($relativePath, $binary);

            $export->update([
                'status' => WasteStreamCollectionReportExport::STATUS_COMPLETED,
                'path' => $relativePath,
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            report($e);
            $export->update([
                'status' => WasteStreamCollectionReportExport::STATUS_FAILED,
                'error_message' => 'The report could not be generated. Please try again or contact support if the problem continues.',
            ]);
        }
    }
}
