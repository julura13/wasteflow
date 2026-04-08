<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Material;
use App\Models\OrderWasteStream;
use App\Models\Site;
use App\Models\WasteStream;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard and report aggregations directly from order_waste_streams joined to finalized orders.
 * Avoids client_monthly_materials_summary so grade summary, daily drill-down, and charts share one source of truth.
 *
 * @phpstan-type WasteStreamTotalRow array{name: string, value: float, color: string}
 * @phpstan-type ClassificationSlice array{total: float, percentage: float}
 */
class OrderWasteStreamReportingService
{
    /**
     * Grade (waste stream) totals by calendar month for a year — same shape as legacy ClientMonthlyMaterialSummary-based rows.
     *
     * @return array<int, array{name: string, jan: float, feb: float, mar: float, apr: float, may: float, jun: float, jul: float, aug: float, sep: float, oct: float, nov: float, dec: float, total: float}>
     */
    public function gradeSummaryForYear(?Company $company, ?Branch $branch, ?Site $site, int $year): array
    {
        $monthNames = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        $yearStart = sprintf('%04d-01-01', $year);
        $yearEnd = sprintf('%04d-12-31', $year);

        $query = $this->baseJoinedStreamsQuery($company, $branch, $site)
            ->whereRaw(
                'DATE(COALESCE(orders.actual_collection_date, orders.requested_collection_date)) BETWEEN ? AND ?',
                [$yearStart, $yearEnd]
            )
            ->groupBy(
                'waste_streams.name',
                DB::raw('MONTH(COALESCE(orders.actual_collection_date, orders.requested_collection_date))')
            )
            ->selectRaw('waste_streams.name as stream_name')
            ->selectRaw('MONTH(COALESCE(orders.actual_collection_date, orders.requested_collection_date)) as cal_month')
            ->selectRaw('SUM(order_waste_streams.nett_weight) as total_weight');

        $results = $query->get();

        $byStream = [];
        foreach ($results as $row) {
            $name = trim((string) $row->stream_name);
            $month = (int) $row->cal_month;
            if ($month < 1 || $month > 12) {
                continue;
            }
            if (! isset($byStream[$name])) {
                $byStream[$name] = array_merge(
                    array_combine($monthNames, array_fill(0, 12, 0.0)),
                    ['name' => $name, 'total' => 0.0]
                );
            }
            $weight = (float) $row->total_weight;
            $byStream[$name][$monthNames[$month - 1]] += $weight;
            $byStream[$name]['total'] += $weight;
        }

        $out = [];
        foreach ($byStream as $row) {
            foreach ($monthNames as $m) {
                $row[$m] = round($row[$m], 2);
            }
            $row['total'] = round($row['total'], 2);
            $out[] = $row;
        }
        usort($out, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $out;
    }

    /**
     * Per-material nett weight totals for a date range (for dashboard pies + environmental impact).
     *
     * @return Collection<int, object{material_id: int, total_weight: float, material: Material}>
     */
    public function materialWeightAggregatesForDateRange(
        ?Company $company,
        ?Branch $branch,
        ?Site $site,
        string $fromDate,
        string $toDate
    ): Collection {
        $sums = $this->baseJoinedStreamsQuery($company, $branch, $site)
            ->whereRaw(
                'DATE(COALESCE(orders.actual_collection_date, orders.requested_collection_date)) BETWEEN ? AND ?',
                [$fromDate, $toDate]
            )
            ->selectRaw('order_waste_streams.material_id as material_id')
            ->selectRaw('SUM(order_waste_streams.nett_weight) as total_weight')
            ->groupBy('order_waste_streams.material_id')
            ->get();

        if ($sums->isEmpty()) {
            return collect();
        }

        $materials = Material::query()
            ->with(['wasteStream', 'grade', 'classification'])
            ->whereIn('id', $sums->pluck('material_id'))
            ->get()
            ->keyBy('id');

        return $sums->map(function ($row) use ($materials) {
            $material = $materials->get($row->material_id);
            if (! $material) {
                return null;
            }

            return (object) [
                'material_id' => (int) $row->material_id,
                'total_weight' => (float) $row->total_weight,
                'material' => $material,
            ];
        })->filter()->values();
    }

    /**
     * Daily breakdown rows for one waste stream in one month (grade summary drill-down).
     *
     * @return array{rows: array<int, array<string, mixed>>, waste_stream: string, month: int, year: int, days_in_month: int}
     */
    public function gradeMonthDailyDetail(
        ?Company $company,
        ?Branch $branch,
        ?Site $site,
        string $wasteStreamName,
        int $month,
        int $year
    ): array {
        $stream = WasteStream::where('name', $wasteStreamName)->first();
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        if (! $stream) {
            return [
                'rows' => [],
                'waste_stream' => $wasteStreamName,
                'month' => $month,
                'year' => $year,
                'days_in_month' => $lastDay,
            ];
        }

        $start = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        $end = Carbon::createFromDate($year, $month, $lastDay)->format('Y-m-d');

        $streams = $this->baseJoinedStreamsQuery($company, $branch, $site)
            ->where('materials.waste_stream_id', $stream->id)
            ->whereRaw(
                'DATE(COALESCE(orders.actual_collection_date, orders.requested_collection_date)) BETWEEN ? AND ?',
                [$start, $end]
            )
            ->with(['order', 'material.grade', 'material.wasteStream'])
            ->select('order_waste_streams.*')
            ->get();

        $byMaterialDay = [];
        foreach ($streams as $ows) {
            $date = $ows->order->actual_collection_date ?? $ows->order->requested_collection_date;
            if (! $date) {
                continue;
            }
            $date = Carbon::parse($date);
            if ($date->month !== $month || $date->year !== $year) {
                continue;
            }
            $day = $date->day;
            $mid = $ows->material_id;
            if (! isset($byMaterialDay[$mid])) {
                $byMaterialDay[$mid] = [
                    'name' => $ows->material && $ows->material->grade
                        ? trim($ows->material->grade->name)
                        : ($ows->material ? 'Material #'.$ows->material_id : 'Unknown'),
                    'days' => array_fill(1, 31, 0.0),
                ];
            }
            $byMaterialDay[$mid]['days'][$day] = ($byMaterialDay[$mid]['days'][$day] ?? 0) + (float) $ows->nett_weight;
        }

        $rows = [];
        foreach ($byMaterialDay as $data) {
            $row = ['name' => $data['name'], 'total' => 0.0];
            $monthRaw = 0.0;
            for ($d = 1; $d <= $lastDay; $d++) {
                $raw = (float) ($data['days'][$d] ?? 0);
                $monthRaw += $raw;
                $row['day'.$d] = round($raw, 2);
            }
            $row['total'] = round($monthRaw, 2);
            $rows[] = $row;
        }
        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return [
            'rows' => $rows,
            'waste_stream' => $wasteStreamName,
            'month' => $month,
            'year' => $year,
            'days_in_month' => $lastDay,
        ];
    }

    /**
     * Waste stream weights for the main dashboard / report pie (same palette as dashboard).
     *
     * @param  Collection<int, object{material_id: int, total_weight: float, material: Material}>  $materialSummaries
     * @return array<int, WasteStreamTotalRow>
     */
    public function wasteStreamTotalsFromSummaries(Collection $materialSummaries): array
    {
        $totals = [];

        foreach ($materialSummaries as $summary) {
            if (! $summary->material || ! $summary->material->wasteStream) {
                continue;
            }

            $wasteStreamName = trim($summary->material->wasteStream->name);
            $weight = (float) $summary->total_weight;

            if (! isset($totals[$wasteStreamName])) {
                $totals[$wasteStreamName] = 0;
            }
            $totals[$wasteStreamName] += $weight;
        }

        $colors = [
            'Paper' => '#2F80ED',
            'Plastic' => '#F2994A',
            'Organic Waste' => '#27AE60',
            'Waste' => '#1C1C1C',
            'General Waste' => '#1C1C1C',
            'Glass' => '#56CCF2',
            'Metal' => '#4F4F4F',
            'Aluminium' => '#BDBDBD',
            'Aluminum' => '#BDBDBD',
            'Woven Bags' => '#8D6E63',
            'Wood' => '#A0522D',
            'Hazardous Waste' => '#EB5757',
            'Garden Waste' => '#27AE60',
            'Recycling' => '#6FCF97',
        ];

        $result = [];
        foreach ($totals as $name => $weight) {
            $result[] = [
                'name' => $name,
                'value' => round($weight, 2),
                'color' => $colors[$name] ?? '#828282',
            ];
        }

        return $result;
    }

    /**
     * Classification donut inputs for dashboard and monthly PDF (diverted = avoidance + recycling + recovery).
     *
     * @param  Collection<int, object{material_id: int, total_weight: float, material: Material}>  $materialSummaries
     * @return array{
     *     avoidance: ClassificationSlice,
     *     recycling: ClassificationSlice,
     *     recovery: ClassificationSlice,
     *     disposal: ClassificationSlice,
     *     diverted: ClassificationSlice
     * }
     */
    public function classificationTotalsFromSummaries(Collection $materialSummaries): array
    {
        $totals = [
            'Avoidance' => 0.0,
            'Recycling' => 0.0,
            'Recovery' => 0.0,
            'Disposal' => 0.0,
        ];

        foreach ($materialSummaries as $summary) {
            if (! $summary->material || ! $summary->material->classification_id || ! $summary->material->classification) {
                continue;
            }

            $classificationName = trim($summary->material->classification->name);
            $weight = (float) $summary->total_weight;
            $classificationNameLower = strtolower($classificationName);

            if (in_array($classificationNameLower, ['disposed', 'disposal'], true)) {
                $totals['Disposal'] += $weight;
            } elseif (in_array($classificationNameLower, ['recycling', 'recycle'], true)) {
                $totals['Recycling'] += $weight;
            } elseif (in_array($classificationNameLower, ['recovered', 'recovery'], true)) {
                $totals['Recovery'] += $weight;
            } elseif (in_array($classificationNameLower, ['avoidance', 'avoid'], true)) {
                $totals['Avoidance'] += $weight;
            }
        }

        $totalWeight = array_sum($totals);
        $divertedTotal = $totals['Avoidance'] + $totals['Recycling'] + $totals['Recovery'];

        return [
            'avoidance' => [
                'total' => round($totals['Avoidance'], 2),
                'percentage' => $totalWeight > 0 ? round(($totals['Avoidance'] / $totalWeight) * 100, 1) : 0,
            ],
            'recycling' => [
                'total' => round($totals['Recycling'], 2),
                'percentage' => $totalWeight > 0 ? round(($totals['Recycling'] / $totalWeight) * 100, 1) : 0,
            ],
            'recovery' => [
                'total' => round($totals['Recovery'], 2),
                'percentage' => $totalWeight > 0 ? round(($totals['Recovery'] / $totalWeight) * 100, 1) : 0,
            ],
            'disposal' => [
                'total' => round($totals['Disposal'], 2),
                'percentage' => $totalWeight > 0 ? round(($totals['Disposal'] / $totalWeight) * 100, 1) : 0,
            ],
            'diverted' => [
                'total' => round($divertedTotal, 2),
                'percentage' => $totalWeight > 0 ? round(($divertedTotal / $totalWeight) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * @param  Builder<\App\Models\OrderWasteStream>  $query
     */
    protected function applyClientCompanyConstraint(Builder $query): void
    {
        $user = Auth::user();
        if (! $user || ! $user->company_id) {
            return;
        }
        if ($user->can('view-reports-all')) {
            return;
        }
        $query->where('orders.company_id', $user->company_id);
    }

    /**
     * Finalized lines with positive nett weight, material present; joins orders + materials + waste_streams.
     *
     * @return Builder<\App\Models\OrderWasteStream>
     */
    protected function baseJoinedStreamsQuery(?Company $company, ?Branch $branch, ?Site $site): Builder
    {
        $query = OrderWasteStream::query()
            ->join('orders', 'order_waste_streams.order_id', '=', 'orders.id')
            ->join('materials', 'order_waste_streams.material_id', '=', 'materials.id')
            ->join('waste_streams', 'materials.waste_stream_id', '=', 'waste_streams.id')
            ->where('orders.status', 'finalized')
            ->whereNotNull('order_waste_streams.material_id')
            ->where('order_waste_streams.nett_weight', '>', 0);

        if ($site) {
            $query->where('orders.site_id', $site->id);
        } elseif ($branch) {
            $query->where('orders.branch_id', $branch->id);
        } elseif ($company) {
            $query->where('orders.company_id', $company->id);
        }

        $this->applyClientCompanyConstraint($query);

        return $query;
    }
}
