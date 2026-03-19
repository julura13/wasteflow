<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Classification;
use App\Models\ClientMonthlyMaterialSummary;
use App\Models\Company;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
use App\Models\Site;
use App\Models\WasteStream;
use App\Services\WasteImpactCalculator;
use App\Traits\ScopeByClientTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use ScopeByClientTrait;

    public function __construct(
        private WasteImpactCalculator $wasteImpactCalculator
    ) {}

    /**
     * Display the dashboard
     */
    public function index(Request $request)
    {
        $companies = $this->scopeCompaniesForUser();

        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;
        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        // Default: 1st of current month to today
        $fromDate = $request->input('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::now()->format('Y-m-d'));

        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::find($branchId) : null;
        $site = $siteId ? Site::find($siteId) : null;

        $dashboardData = $this->getDashboardData($company, $branch, $site, $fromDate, $toDate);

        $year = Carbon::parse($fromDate)->year;
        $gradeSummaryByYear = $this->getGradeSummaryForYear($company, $branch, $site, $year);

        $ordersNearDates = $this->getOrdersForNearDates($companyId, $branchId, $siteId);

        return Inertia::render('Dashboard', [
            'companies' => $companies,
            'dashboardData' => $dashboardData,
            'gradeSummaryByYear' => $gradeSummaryByYear,
            'ordersNearDates' => $ordersNearDates,
            'filters' => [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
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
     * Get daily weight breakdown for a waste stream (grade) in a given month.
     * Used when clicking a grade+month cell in the Grade Summary table.
     */
    public function getGradeMonthDailyDetail(Request $request)
    {
        $wasteStreamName = $request->input('waste_stream');
        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;

        if (! $wasteStreamName || ! $month || ! $year) {
            return response()->json(['rows' => [], 'waste_stream' => $wasteStreamName, 'month' => $month, 'year' => $year, 'days_in_month' => 0], 400);
        }

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $stream = WasteStream::where('name', $wasteStreamName)->first();
        if (! $stream) {
            return response()->json(['rows' => [], 'waste_stream' => $wasteStreamName, 'month' => $month, 'year' => $year, 'days_in_month' => cal_days_in_month(CAL_GREGORIAN, $month, $year)]);
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay()->format('Y-m-d');
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $end = Carbon::createFromDate($year, $month, $lastDay)->format('Y-m-d');

        $streams = OrderWasteStream::query()
            ->with(['order', 'material.grade', 'material.wasteStream'])
            ->whereHas('order', function ($q) use ($start, $end, $companyId, $branchId, $siteId) {
                $q->where('status', 'finalized')
                    ->where(function ($q2) use ($start, $end) {
                        $q2->whereBetween('actual_collection_date', [$start, $end])
                            ->orWhere(function ($q3) use ($start, $end) {
                                $q3->whereNull('actual_collection_date')
                                    ->whereBetween('requested_collection_date', [$start, $end]);
                            });
                    });
                if ($siteId) {
                    $q->where('site_id', $siteId);
                } elseif ($branchId) {
                    $q->where('branch_id', $branchId);
                } elseif ($companyId) {
                    $q->where('company_id', $companyId);
                }
                if ($this->isClientScoped() && auth()->user()->company_id) {
                    $q->where('company_id', auth()->user()->company_id);
                }
            })
            ->whereHas('material', fn ($q) => $q->where('waste_stream_id', $stream->id))
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
                $byMaterialDay[$mid] = ['name' => $ows->material && $ows->material->grade
                    ? trim($ows->material->grade->name)
                    : ($ows->material ? 'Material #'.$ows->material_id : 'Unknown'),
                    'days' => array_fill(1, 31, 0),
                ];
            }
            $byMaterialDay[$mid]['days'][$day] = ($byMaterialDay[$mid]['days'][$day] ?? 0) + (float) $ows->nett_weight;
        }

        $rows = [];
        foreach ($byMaterialDay as $materialId => $data) {
            $row = ['name' => $data['name'], 'total' => 0];
            for ($d = 1; $d <= $lastDay; $d++) {
                $w = round((float) ($data['days'][$d] ?? 0), 2);
                $row['day'.$d] = $w;
                $row['total'] += $w;
            }
            $row['total'] = round($row['total'], 2);
            $rows[] = $row;
        }
        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return response()->json([
            'rows' => $rows,
            'waste_stream' => $wasteStreamName,
            'month' => $month,
            'year' => $year,
            'days_in_month' => $lastDay,
        ]);
    }

    /**
     * Get finalized orders for a specific day (optionally filtered by waste stream).
     * Used when clicking a day cell in the grade-month daily detail table.
     */
    public function getOrdersForDay(Request $request)
    {
        $date = $request->input('date');
        $wasteStreamName = $request->input('waste_stream');
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;

        if (! $date) {
            return response()->json(['orders' => []], 400);
        }

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $query = Order::query()
            ->with(['site:id,name,branch_id', 'site.branch:id,name,company_id', 'site.branch.company:id,name'])
            ->where('status', 'finalized')
            ->where(function ($q) use ($date) {
                $q->where('actual_collection_date', $date)
                    ->orWhere(function ($q2) use ($date) {
                        $q2->whereNull('actual_collection_date')
                            ->where('requested_collection_date', $date);
                    });
            });

        if ($siteId) {
            $query->where('site_id', $siteId);
        } elseif ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($this->isClientScoped() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($wasteStreamName) {
            $stream = WasteStream::where('name', $wasteStreamName)->first();
            if ($stream) {
                $query->whereHas('wasteStreams', function ($q) use ($stream) {
                    $q->whereHas('material', fn ($m) => $m->where('waste_stream_id', $stream->id));
                });
            }
        }

        $orders = $query->orderBy('actual_collection_date')->orderBy('requested_collection_date')->orderBy('id')
            ->get(['id', 'tracking_number', 'company_id', 'branch_id', 'site_id', 'order_type', 'status', 'waste_type', 'quantity_type', 'quantity', 'requested_collection_date', 'actual_collection_date']);

        $list = $orders->map(function ($order) {
            $d = $order->actual_collection_date ?? $order->requested_collection_date;

            return [
                'id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'waste_type' => $order->waste_type,
                'quantity_type' => $order->quantity_type,
                'quantity' => $order->quantity,
                'collection_date' => $d ? $d->format('Y-m-d') : null,
                'site' => $order->site ? ['name' => $order->site->name] : null,
            ];
        })->values()->all();

        return response()->json(['orders' => $list]);
    }

    /**
     * Get dashboard data based on filters
     * If no company/branch/site is selected, aggregates all companies
     */
    private function getDashboardData(?Company $company, ?Branch $branch, ?Site $site, string $fromDate, string $toDate): array
    {
        $startDate = Carbon::parse($fromDate);
        $endDate = Carbon::parse($toDate);

        // Get all months in the date range
        $months = [];
        $current = $startDate->copy()->startOfMonth();
        while ($current->lte($endDate)) {
            $months[] = [
                'year' => $current->year,
                'month' => $current->month,
            ];
            $current->addMonth();
        }

        // Aggregate summaries across all months in the date range
        $summaries = collect();
        foreach ($months as $monthData) {
            $monthSummaries = $this->getSummariesForMonth($company, $branch, $site, $monthData['month'], $monthData['year']);
            $summaries = $summaries->merge($monthSummaries);
        }

        // Group by material_id and sum weights (for material-level data)
        // Aggregate across all company/branch/site combinations for the same material
        $grouped = $summaries
            ->whereNotNull('material_id')
            ->groupBy('material_id');

        $materialSummaries = collect();
        foreach ($grouped as $materialId => $group) {
            $first = $group->first();
            // Ensure material relationship is loaded with classification
            if (! $first->relationLoaded('material')) {
                $first->load('material.wasteStream', 'material.grade', 'material.classification');
            }

            // Reload material if classification is missing
            if ($first->material && ! $first->material->relationLoaded('classification') && $first->material->classification_id) {
                $first->material->load('classification');
            }

            $materialSummaries->push((object) [
                'material_id' => $materialId,
                'total_weight' => $group->sum('total_weight'),
                'material' => $first->material,
            ]);
        }

        // Get waste stream totals (main pie chart)
        $wasteStreamTotals = $this->getWasteStreamTotals($materialSummaries);

        // Get classification totals (4 smaller pie charts)
        $classificationTotals = $this->getClassificationTotals($materialSummaries);

        // Calculate environmental impact (shared with report and summary)
        $categoryWeights = $this->wasteImpactCalculator->buildCategoryWeightsFromSummaries($materialSummaries);
        $environmentalImpact = $this->wasteImpactCalculator->calculateImpactFromCategoryWeights($categoryWeights);

        return [
            'wasteStreamTotals' => $wasteStreamTotals,
            'classificationTotals' => $classificationTotals,
            'environmentalImpact' => $environmentalImpact,
        ];
    }

    /**
     * Get summaries for a specific month
     * If no company/branch/site is provided, returns all summaries
     */
    private function getSummariesForMonth(?Company $company, ?Branch $branch, ?Site $site, int $month, int $year)
    {
        $query = ClientMonthlyMaterialSummary::query()
            ->where('year', $year)
            ->where('month', $month)
            ->with(['material.wasteStream', 'material.grade', 'material.classification']);

        // Filter by site (most specific) - only this site
        if ($site) {
            $query->where('site_id', $site->id);
        }
        // Filter by branch - all sites under this branch
        elseif ($branch) {
            $query->where('branch_id', $branch->id);
            // Include both site-level and branch-level summaries
        }
        // Filter by company - all branches and sites under this company
        elseif ($company) {
            $query->where('company_id', $company->id);
            // Include company-level, branch-level, and site-level summaries
        }
        // No filters - get all companies (aggregate all summaries)
        // No additional where clause needed - query will return all

        return $query->get();
    }

    /**
     * Get grade (waste stream) summary by month for a full year.
     * Returns array of rows: [ 'name' => 'Paper', 'jan' => 100, 'feb' => 200, ..., 'total' => 1500 ]
     */
    private function getGradeSummaryForYear(?Company $company, ?Branch $branch, ?Site $site, int $year): array
    {
        $monthNames = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        $byStream = [];

        for ($month = 1; $month <= 12; $month++) {
            $summaries = $this->getSummariesForMonth($company, $branch, $site, $month, $year);
            foreach ($summaries as $summary) {
                if (! $summary->material || ! $summary->material->wasteStream) {
                    continue;
                }
                $name = trim($summary->material->wasteStream->name);
                if (! isset($byStream[$name])) {
                    $byStream[$name] = array_combine($monthNames, array_fill(0, 12, 0));
                    $byStream[$name]['name'] = $name;
                    $byStream[$name]['total'] = 0;
                }
                $weight = (float) $summary->total_weight;
                // Accumulate: multiple materials can share the same waste stream in a month
                $byStream[$name][$monthNames[$month - 1]] += $weight;
                $byStream[$name]['total'] += $weight;
            }
        }

        $rows = [];
        foreach ($byStream as $row) {
            // Round each month and total for display
            foreach ($monthNames as $m) {
                $row[$m] = round($row[$m], 2);
            }
            $row['total'] = round($row['total'], 2);
            $rows[] = $row;
        }
        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * Get orders for yesterday, today, and tomorrow (for selected company/branch/site).
     */
    private function getOrdersForNearDates(?int $companyId, ?int $branchId, ?int $siteId): array
    {
        $yesterday = Carbon::today()->subDay();
        $tomorrow = Carbon::today()->addDay();
        $start = $yesterday->format('Y-m-d');
        $end = $tomorrow->format('Y-m-d');

        $query = Order::query()
            ->with(['site:id,name,branch_id', 'site.branch:id,name,company_id', 'site.branch.company:id,name', 'serviceProvider:id,name'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('requested_collection_date', [$start, $end])
                    ->orWhereBetween('actual_collection_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNull('actual_collection_date')
                            ->whereBetween('requested_collection_date', [$start, $end]);
                    });
            });

        if ($siteId) {
            $query->where('site_id', $siteId);
        } elseif ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($this->isClientScoped() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        $orders = $query->orderBy('requested_collection_date')->orderBy('actual_collection_date')->orderBy('id')
            ->get(['id', 'tracking_number', 'company_id', 'branch_id', 'site_id', 'service_provider_id', 'order_type', 'status', 'requested_collection_date', 'actual_collection_date']);

        return $orders->map(function ($order) {
            $date = $order->actual_collection_date ?? $order->requested_collection_date;

            return [
                'id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'collection_date' => $date ? $date->format('Y-m-d') : null,
                'site' => $order->site ? ['name' => $order->site->name] : null,
                'service_provider' => $order->serviceProvider?->name,
            ];
        })->values()->all();
    }

    /**
     * Get waste stream totals for the main pie chart
     */
    private function getWasteStreamTotals($materialSummaries): array
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

        // Convert to array format for charts
        $colors = [
            'Paper' => '#60a5fa',
            'Plastic' => '#3b82f6',
            'Waste' => '#fbbf24',
            'Metal' => '#ef4444',
            'Glass' => '#3b82f6',
            'Aluminium' => '#8b5cf6',
            'Organic Waste' => '#10b981',
            'Garden Waste' => '#84cc16',
            'Hazardous Waste' => '#dc2626',
        ];

        $result = [];
        foreach ($totals as $name => $weight) {
            $result[] = [
                'name' => $name,
                'value' => round($weight, 2),
                'color' => $colors[$name] ?? '#6b7280',
            ];
        }

        return $result;
    }

    /**
     * Get classification totals for the 4 pie charts
     * Groups by classification_id and sums weights
     */
    private function getClassificationTotals($materialSummaries): array
    {
        $totals = [
            'Avoidance' => 0,
            'Recycling' => 0,
            'Recovery' => 0,
            'Disposal' => 0,
        ];

        foreach ($materialSummaries as $summary) {
            if (! $summary->material || ! $summary->material->classification_id || ! $summary->material->classification) {
                continue;
            }

            $classificationName = trim($summary->material->classification->name);
            $weight = (float) $summary->total_weight;

            // Map classification names from database to our display categories
            // Handle case-insensitive matching and variations
            $classificationNameLower = strtolower($classificationName);

            if (in_array($classificationNameLower, ['disposed', 'disposal'])) {
                $totals['Disposal'] += $weight;
            } elseif (in_array($classificationNameLower, ['recycling', 'recycle'])) {
                $totals['Recycling'] += $weight;
            } elseif (in_array($classificationNameLower, ['recovered', 'recovery'])) {
                $totals['Recovery'] += $weight;
            } elseif (in_array($classificationNameLower, ['avoidance', 'avoid'])) {
                $totals['Avoidance'] += $weight;
            }
        }

        $totalWeight = array_sum($totals);

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
        ];
    }

    /**
     * Get empty dashboard data structure
     */
    private function getEmptyDashboardData(): array
    {
        return [
            'wasteStreamTotals' => [],
            'classificationTotals' => [
                'avoidance' => ['total' => 0, 'percentage' => 0],
                'recycling' => ['total' => 0, 'percentage' => 0],
                'recovery' => ['total' => 0, 'percentage' => 0],
                'disposal' => ['total' => 0, 'percentage' => 0],
            ],
            'environmentalImpact' => [
                'treesSaved' => 0,
                'energySaved' => 0,
                'waterSaved' => 0,
                'co2Saved' => 0,
                'electricityEquivalentKwhSaGrid' => 0,
                'transportEquivalentKm' => 0,
                'fuelEquivalentLitresPetrol' => 0,
                'carsOffRoadAnnualEquivalent' => 0,
            ],
        ];
    }
}
