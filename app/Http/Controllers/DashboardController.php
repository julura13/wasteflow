<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\Site;
use App\Models\WasteStream;
use App\Services\LandfillSpaceCalculator;
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
        private LandfillSpaceCalculator $landfillSpaceCalculator,
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
        $containerSummaryByYear = $this->orderWasteStreamReporting->containerSummaryForYear($company, $branch, $site, $year);

        $ordersNearDates = $this->getOrdersForNearDates($companyId, $branchId, $siteId);

        return Inertia::render('Dashboard', [
            'companies' => $companies,
            'dashboardData' => $dashboardData,
            'gradeSummaryByYear' => $gradeSummaryByYear,
            'containerSummaryByYear' => $containerSummaryByYear,
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
     * Daily container quantity counts for a specific container type in a month.
     * Used when clicking a month cell in the Container Summary table.
     */
    public function getContainerMonthDailyDetail(Request $request)
    {
        $containerOptionName = $request->input('container_option_name', '');
        $description = $request->input('description', '');
        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;

        if (! $containerOptionName || ! $month || ! $year) {
            return response()->json(['counts' => [], 'days_in_month' => 0], 400);
        }

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::find($branchId) : null;
        $site = $siteId ? Site::find($siteId) : null;

        $payload = $this->orderWasteStreamReporting->containerMonthDailyDetail(
            $company,
            $branch,
            $site,
            (string) $containerOptionName,
            (string) $description,
            $month,
            $year
        );

        return response()->json($payload);
    }

    /**
     * Finalized waste orders for a specific day filtered by container type.
     * Used when clicking a day cell in the container daily detail panel.
     */
    public function getOrdersForDayByContainer(Request $request)
    {
        $date = $request->input('date');
        $containerOptionName = $request->input('container_option_name', '');
        $description = $request->input('description', '');
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;

        if (! $date) {
            return response()->json(['orders' => []], 400);
        }

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $query = Order::query()
            ->with(['site:id,name,branch_id', 'site.branch:id,name,company_id', 'site.branch.company:id,name'])
            ->where([
                'status' => 'finalized',
                'order_type' => 'waste',
            ])
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

        $orders = $query
            ->whereNotNull('quantity_lines')
            ->orderBy('actual_collection_date')
            ->orderBy('requested_collection_date')
            ->orderBy('id')
            ->get(['id', 'tracking_number', 'company_id', 'branch_id', 'site_id', 'order_type', 'status', 'requested_collection_date', 'actual_collection_date', 'quantity_lines']);

        $list = $orders
            ->filter(function ($order) use ($containerOptionName, $description) {
                $lines = $order->quantity_lines;
                if (! is_array($lines)) {
                    return false;
                }
                foreach ($lines as $line) {
                    $lineName = trim((string) ($line['container_option_name'] ?? ''));
                    $lineDesc = trim((string) ($line['description'] ?? ''));
                    if ($lineName === $containerOptionName && $lineDesc === $description) {
                        return true;
                    }
                }

                return false;
            })
            ->map(function ($order) use ($containerOptionName, $description) {
                $d = $order->actual_collection_date ?? $order->requested_collection_date;
                $qty = 0;
                foreach ($order->quantity_lines as $line) {
                    if (trim((string) ($line['container_option_name'] ?? '')) === $containerOptionName &&
                        trim((string) ($line['description'] ?? '')) === $description) {
                        $qty += (int) ($line['quantity'] ?? 0);
                    }
                }

                return [
                    'id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'status' => $order->status,
                    'quantity' => $qty,
                    'collection_date' => $d ? $d->format('Y-m-d') : null,
                    'site' => $order->site ? ['name' => $order->site->name] : null,
                ];
            })
            ->values()
            ->all();

        return response()->json(['orders' => $list]);
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

        $wasteStreamTotals = $this->orderWasteStreamReporting->wasteStreamTotalsFromSummaries($materialSummaries);

        $classificationTotals = $this->orderWasteStreamReporting->classificationTotalsFromSummaries($materialSummaries);

        // Calculate environmental impact using the accurate carbon-key path (split plastics, garden waste included)
        $carbonWeights = $this->wasteImpactCalculator->buildCarbonWeightsFromSummaries($materialSummaries);
        $environmentalImpact = $this->wasteImpactCalculator->calculateImpactFromCarbonWeights($carbonWeights);

        $landfillSpaceSaved = $this->getLandfillSpaceSavedForDashboard(WasteImpactCalculator::toSimpleWeights($carbonWeights));

        return [
            'wasteStreamTotals' => $wasteStreamTotals,
            'classificationTotals' => $classificationTotals,
            'environmentalImpact' => $environmentalImpact,
            'landfillSpaceSaved' => $landfillSpaceSaved,
        ];
    }

    /**
     * Landfill airspace avoided (m³) uses {@see LandfillSpaceCalculator} with the same category mapping as
     * environmental impact (aligned with the landfill space calculator densities).
     *
     * @param  array<string, float>  $categoryWeights  From {@see WasteImpactCalculator::buildCategoryWeightsFromSummaries()}
     * @return array{total_m3: float}
     */
    private function getLandfillSpaceSavedForDashboard(array $categoryWeights): array
    {
        $breakdown = $this->landfillSpaceCalculator->calculate($categoryWeights);
        $totalM3 = (float) ($breakdown['total'] ?? 0);

        return [
            'total_m3' => round($totalM3, 2),
        ];
    }

    /**
     * Get orders from seven days before today through tomorrow (for selected company/branch/site).
     */
    private function getOrdersForNearDates(?int $companyId, ?int $branchId, ?int $siteId): array
    {
        $start = Carbon::today()->subDays(7)->format('Y-m-d');
        $end = Carbon::today()->addDay()->format('Y-m-d');

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
            ->get([
                'id',
                'tracking_number',
                'company_id',
                'branch_id',
                'site_id',
                'service_provider_id',
                'order_type',
                'status',
                'quantity',
                'quantity_type',
                'quantity_lines',
                'requested_collection_date',
                'actual_collection_date',
            ]);

        return $orders->map(function ($order) {
            $date = $order->actual_collection_date ?? $order->requested_collection_date;

            return [
                'id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'quantity' => $order->quantity,
                'quantity_type' => $order->quantity_type,
                'quantity_lines' => $order->quantity_lines,
                'collection_date' => $date ? $date->format('Y-m-d') : null,
                'site' => $order->site ? ['name' => $order->site->name] : null,
                'service_provider' => $order->serviceProvider?->name,
            ];
        })->values()->all();
    }
}
