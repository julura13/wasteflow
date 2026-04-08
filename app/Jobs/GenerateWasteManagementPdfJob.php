<?php

namespace App\Jobs;

use App\Http\Controllers\ReportController;
use App\Models\User;
use App\Models\WasteManagementReportExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateWasteManagementPdfJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(public int $wasteManagementReportExportId) {}

    public function handle(ReportController $reportController): void
    {
        $export = WasteManagementReportExport::query()->find($this->wasteManagementReportExportId);
        if (! $export) {
            return;
        }

        if ($export->expires_at->isPast()) {
            $export->update([
                'status' => WasteManagementReportExport::STATUS_FAILED,
                'error_message' => 'This export request has expired. Please generate the report again.',
            ]);

            return;
        }

        $export->update(['status' => WasteManagementReportExport::STATUS_PROCESSING, 'error_message' => null]);

        try {
            $user = User::query()->findOrFail($export->user_id);
            $reportController->completeWasteManagementReportExport($export, $user);
        } catch (Throwable $e) {
            report($e);
            $export->update([
                'status' => WasteManagementReportExport::STATUS_FAILED,
                'error_message' => 'The report could not be generated. Please try again or contact support if the problem continues.',
            ]);
        }
    }
}
