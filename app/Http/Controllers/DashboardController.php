<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\Site;
use App\Models\WasteStream;
use App\Services\OrderWasteStreamReportingService;
use App\Services\WasteImpactCalculator;
use App\Traits\ScopeByClientTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use ScopeByClientTrait;

    public function __construct(
        private WasteImpactCalculator $wasteImpactCalculator,
        private OrderWasteStreamReportingService $orderWasteStreamReporting,
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
        $gradeSummaryByYear = $this->orderWasteStreamReporting->gradeSummaryForYear($company, $branch, $site, $year);

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

        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::find($branchId) : null;
        $site = $siteId ? Site::find($siteId) : null;

        $payload = $this->orderWasteStreamReporting->gradeMonthDailyDetail(
            $company,
            $branch,
            $site,
            (string) $wasteStreamName,
            $month,
            $year
        );

        return response()->json($payload);
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

        $materialSummaries = $this->orderWasteStreamReporting->materialWeightAggregatesForDateRange(
            $company,
            $branch,
            $site,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

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
}
