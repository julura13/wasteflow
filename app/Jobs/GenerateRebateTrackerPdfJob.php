<?php

namespace App\Jobs;

use App\Models\RebateReportExport;
use App\Models\User;
use App\Services\CompanyUserService;
use App\Services\RebateTrackerReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateRebateTrackerPdfJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(public int $rebateReportExportId) {}

    public function handle(RebateTrackerReportService $rebateTrackerReportService, CompanyUserService $companyUserService): void
    {
        $export = RebateReportExport::query()->find($this->rebateReportExportId);
        if (! $export) {
            return;
        }

        if ($export->expires_at->isPast()) {
            $export->update([
                'status' => RebateReportExport::STATUS_FAILED,
                'error_message' => 'This export request has expired. Please generate the report again.',
            ]);

            return;
        }

        $export->update(['status' => RebateReportExport::STATUS_PROCESSING, 'error_message' => null]);

        try {
            $user = User::query()->findOrFail($export->user_id);
            $companyIds = $user->isAdmin() ? [] : $companyUserService->getCompanyIdsForUser($user);

            $filters = $export->filters;
            $startDate = $filters['start_date'];
            $endDate = $filters['end_date'];
            $companyId = $filters['company_id'] ?? null;
            $branchId = $filters['branch_id'] ?? null;
            $siteId = $filters['site_id'] ?? null;

            $rebateData = $rebateTrackerReportService->getRebateTrackerData(
                $startDate,
                $endDate,
                $companyId,
                $branchId,
                $siteId,
                $user,
                $companyIds,
            );

            $totalRebate = (float) $rebateData->sum('total');
            $totalWeight = (float) $rebateData->sum('weight');

            $pdfFilters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
            ];

            $binary = $rebateTrackerReportService->renderRebateTrackerPdfBinary($rebateData, $pdfFilters, $totalRebate, $totalWeight);

            $relativePath = 'rebate-reports/'.$export->uuid.'.pdf';
            Storage::disk($export->disk)->put($relativePath, $binary);

            $export->update([
                'status' => RebateReportExport::STATUS_COMPLETED,
                'path' => $relativePath,
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            report($e);
            $export->update([
                'status' => RebateReportExport::STATUS_FAILED,
                'error_message' => 'The report could not be generated. Please try again or contact support if the problem continues.',
            ]);
        }
    }
}
