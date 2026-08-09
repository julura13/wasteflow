<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cross-client "Management Report": one row per company for a single calendar month, showing
 * total waste diverted % and container-type quantity totals — so an admin can review all
 * clients' performance for the month in one place.
 */
class ManagementReportService
{
    public function __construct(
        private readonly OrderWasteStreamReportingService $orderWasteStreamReporting,
    ) {}

    /**
     * @return list<array{
     *     company_id: int,
     *     company_name: string,
     *     total_waste_diverted_percentage: float,
     *     total_waste_managed_kg: float,
     *     container_totals: array<int, array{name: string, quantity: int}>
     * }>
     */
    public function buildForCompanies(Collection $companies, int $month, int $year): array
    {
        if ($companies->isEmpty()) {
            return [];
        }

        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfDay()->format('Y-m-d');
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $monthEnd = Carbon::createFromDate($year, $month, $lastDay)->endOfDay()->format('Y-m-d');

        $rows = [];
        foreach ($companies->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $company) {
            $materialSummaries = $this->orderWasteStreamReporting->materialWeightAggregatesForDateRange(
                $company,
                null,
                null,
                $monthStart,
                $monthEnd,
            );
            $classificationTotals = $this->orderWasteStreamReporting->classificationTotalsFromSummaries($materialSummaries);
            $containerTotals = $this->orderWasteStreamReporting->containerTotalsForMonth($company, null, null, $month, $year);

            $rows[] = [
                'company_id' => (int) $company->id,
                'company_name' => (string) $company->name,
                'total_waste_diverted_percentage' => (float) ($classificationTotals['diverted']['percentage'] ?? 0),
                'total_waste_managed_kg' => (float) ($classificationTotals['total'] ?? 0),
                'container_totals' => $containerTotals,
            ];
        }

        return $rows;
    }
}
