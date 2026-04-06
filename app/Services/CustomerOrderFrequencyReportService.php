<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CustomerOrderFrequencyReportService
{
    /** @var list<string> */
    private const ORDER_TYPES = ['waste', 'recycling'];

    /**
     * @return list<array{
     *     company_id: int,
     *     company_name: string,
     *     waste: array{
     *         last_finalized_date: string|null,
     *         days_since_last_finalized: int|null,
     *         finalized_orders_in_period: int,
     *         average_orders_per_month: float
     *     },
     *     recycling: array{
     *         last_finalized_date: string|null,
     *         days_since_last_finalized: int|null,
     *         finalized_orders_in_period: int,
     *         average_orders_per_month: float
     *     }
     * }>
     */
    public function buildForCompanies(Collection $companies, int $lookbackMonths, Carbon $asOf): array
    {
        if ($companies->isEmpty()) {
            return [];
        }

        $companyIds = $companies->pluck('id')->all();
        $asOfDay = $asOf->copy()->startOfDay();
        $periodStart = $asOf->copy()->subMonths($lookbackMonths)->startOfDay();

        $dateExpr = 'COALESCE(orders.actual_collection_date, DATE(orders.updated_at))';

        $lastByCompanyType = Order::query()
            ->where('orders.status', 'finalized')
            ->whereIn('orders.company_id', $companyIds)
            ->whereIn('orders.order_type', self::ORDER_TYPES)
            ->selectRaw("orders.company_id, orders.order_type, MAX({$dateExpr}) as last_finalized")
            ->groupBy('orders.company_id', 'orders.order_type')
            ->get();

        $lastMap = [];
        foreach ($lastByCompanyType as $row) {
            $lastMap[(int) $row->company_id][(string) $row->order_type] = $row->last_finalized;
        }

        $countRows = Order::query()
            ->where('orders.status', 'finalized')
            ->whereIn('orders.company_id', $companyIds)
            ->whereIn('orders.order_type', self::ORDER_TYPES)
            ->whereRaw("{$dateExpr} BETWEEN ? AND ?", [
                $periodStart->toDateString(),
                $asOfDay->toDateString(),
            ])
            ->selectRaw('orders.company_id, orders.order_type, COUNT(*) as order_count')
            ->groupBy('orders.company_id', 'orders.order_type')
            ->get();

        $countMap = [];
        foreach ($countRows as $row) {
            $countMap[(int) $row->company_id][(string) $row->order_type] = (int) $row->order_count;
        }

        $rows = [];
        foreach ($companies->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $company) {
            $cid = (int) $company->id;
            $waste = $this->metricsForOrderType(
                $lastMap[$cid]['waste'] ?? null,
                (int) ($countMap[$cid]['waste'] ?? 0),
                $lookbackMonths,
                $asOfDay
            );
            $recycling = $this->metricsForOrderType(
                $lastMap[$cid]['recycling'] ?? null,
                (int) ($countMap[$cid]['recycling'] ?? 0),
                $lookbackMonths,
                $asOfDay
            );

            $rows[] = [
                'company_id' => $cid,
                'company_name' => (string) $company->name,
                'waste' => $waste,
                'recycling' => $recycling,
            ];
        }

        return $rows;
    }

    /**
     * @return array{
     *     last_finalized_date: string|null,
     *     days_since_last_finalized: int|null,
     *     finalized_orders_in_period: int,
     *     average_orders_per_month: float
     * }
     */
    private function metricsForOrderType(mixed $lastRaw, int $countInPeriod, int $lookbackMonths, Carbon $asOfDay): array
    {
        $lastDate = $lastRaw !== null ? Carbon::parse($lastRaw)->startOfDay() : null;

        $daysSince = null;
        if ($lastDate !== null) {
            $daysSince = (int) $lastDate->diffInDays($asOfDay);
        }

        $avgPerMonth = round($countInPeriod / max($lookbackMonths, 1), 2);

        return [
            'last_finalized_date' => $lastDate?->toDateString(),
            'days_since_last_finalized' => $daysSince,
            'finalized_orders_in_period' => $countInPeriod,
            'average_orders_per_month' => $avgPerMonth,
        ];
    }
}
