<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Site;
use App\Traits\ScopeByClientTrait;
use App\Models\ClientMonthlyMaterialSummary;
use App\Models\Classification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use ScopeByClientTrait;

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

        return Inertia::render('Dashboard', [
            'companies' => $companies,
            'dashboardData' => $dashboardData,
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
     * Get dashboard data based on filters
     * If no company/branch/site is selected, aggregates all companies
     */
    private function getDashboardData(?Company $company = null, ?Branch $branch = null, ?Site $site = null, string $fromDate, string $toDate): array
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
            if (!$first->relationLoaded('material')) {
                $first->load('material.wasteStream', 'material.grade', 'material.classification');
            }
            
            // Reload material if classification is missing
            if ($first->material && !$first->material->relationLoaded('classification') && $first->material->classification_id) {
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

        // Calculate environmental impact
        $environmentalImpact = $this->calculateEnvironmentalImpact($materialSummaries);

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
    private function getSummariesForMonth(?Company $company = null, ?Branch $branch = null, ?Site $site = null, int $month, int $year)
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
     * Get waste stream totals for the main pie chart
     */
    private function getWasteStreamTotals($materialSummaries): array
    {
        $totals = [];

        foreach ($materialSummaries as $summary) {
            if (!$summary->material || !$summary->material->wasteStream) {
                continue;
            }

            $wasteStreamName = trim($summary->material->wasteStream->name);
            $weight = (float) $summary->total_weight;

            if (!isset($totals[$wasteStreamName])) {
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
            if (!$summary->material || !$summary->material->classification_id || !$summary->material->classification) {
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
     * Calculate environmental impact (trees, energy, water, CO2)
     */
    private function calculateEnvironmentalImpact($materialSummaries): array
    {
        // Initialize category weights
        $categoryWeights = [
            'paper' => 0,
            'plastics' => 0,
            'aluminium' => 0,
            'organics' => 0,
            'tetrapak' => 0,
            'steel' => 0,
            'glass' => 0,
        ];

        foreach ($materialSummaries as $summary) {
            if (!$summary->material || !$summary->material->grade || !$summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            // Organics: Organic Waste stream, Organics Recovered grade
            if ($wasteStreamName === 'Organic Waste' && $gradeName === 'Organics Recovered') {
                $categoryWeights['organics'] += $weight;
            }
            // Tetrapak: grade name = "Tetrapak" (separate from paper)
            elseif ($gradeName === 'Tetrapak') {
                $categoryWeights['tetrapak'] += $weight;
            }
            // Paper: waste stream = "Paper" (excluding Tetrapak)
            elseif ($wasteStreamName === 'Paper' && $gradeName !== 'Tetrapak') {
                $categoryWeights['paper'] += $weight;
            }
            // Plastics: waste stream = "Plastic"
            elseif ($wasteStreamName === 'Plastic') {
                $categoryWeights['plastics'] += $weight;
            }
            // Aluminium: waste stream = "Aluminium"
            elseif ($wasteStreamName === 'Aluminium') {
                $categoryWeights['aluminium'] += $weight;
            }
            // Steel: waste stream = "Metal" with steel grades
            elseif ($wasteStreamName === 'Metal' && (
                $gradeName === 'Heavy Steel' || 
                $gradeName === 'Light Steel' || 
                $gradeName === 'Light Steel Cans' || 
                $gradeName === 'Light Steel Drums'
            )) {
                $categoryWeights['steel'] += $weight;
            }
            // Glass: waste stream = "Glass"
            elseif ($wasteStreamName === 'Glass') {
                $categoryWeights['glass'] += $weight;
            }
        }

        // Energy Saved factors
        $energyFactors = [
            'paper' => 10,
            'plastics' => 20,
            'aluminium' => 140,
            'organics' => 9,
            'tetrapak' => 2,
            'steel' => 15,
            'glass' => 7,
        ];

        // Water Saved factors
        $waterFactors = [
            'paper' => 7000,
            'plastics' => 50,
            'aluminium' => 0,
            'organics' => 0,
            'tetrapak' => 0,
            'steel' => 0,
            'glass' => 0,
        ];

        // Calculate treesSaved = totalPaperWeight * (20 / 1000)
        $totalPaperWeight = $categoryWeights['paper'];
        $treesSaved = round($totalPaperWeight * (20 / 1000), 2);

        // Calculate energySaved = sum of (weight * energy factor) for each category
        $energySaved = 0;
        foreach ($categoryWeights as $category => $weight) {
            $energySaved += $weight * $energyFactors[$category];
        }
        $energySaved = round($energySaved, 2);

        // Calculate waterSaved = sum of (weight * water factor) for each category
        $waterSaved = 0;
        foreach ($categoryWeights as $category => $weight) {
            $waterSaved += $weight * $waterFactors[$category];
        }
        // Convert to kL (kiloliters) - divide by 1000
        $waterSaved = round($waterSaved / 1000, 2);

        // Calculate CO2 saved (using lifecycle saving calculation)
        $co2Saved = $this->calculateCO2Saved($categoryWeights);

        return [
            'treesSaved' => $treesSaved,
            'energySaved' => $energySaved,
            'waterSaved' => $waterSaved,
            'co2Saved' => $co2Saved,
        ];
    }

    /**
     * Calculate CO2 saved using lifecycle saving calculation
     */
    private function calculateCO2Saved(array $categoryWeights): float
    {
        // Map categories to CO2 calculation keys
        $weights = [
            'paper' => $categoryWeights['paper'],
            'plasticPPHD' => 0, // Would need to break down plastics further
            'plasticPS' => 0,
            'plasticLDPE' => 0,
            'aluminium' => $categoryWeights['aluminium'],
            'steel' => $categoryWeights['steel'],
            'glass' => $categoryWeights['glass'],
            'foodWaste' => $categoryWeights['organics'],
            'gardenWaste' => 0,
            'batteries' => 0,
            'electronics' => 0,
            'tetrapak' => $categoryWeights['tetrapak'],
        ];

        // Add plastics breakdown (simplified - using all plastics as PP/HD for now)
        $weights['plasticPPHD'] = $categoryWeights['plastics'];

        // Emission factors - Scope 3 EF
        $scope3EFFactors = [
            'paper' => 0.092,
            'plasticPPHD' => 0.18,
            'plasticPS' => 0.2,
            'plasticLDPE' => 0.18,
            'aluminium' => 0.5,
            'steel' => 0.25,
            'glass' => 0.09,
            'foodWaste' => 0.05,
            'gardenWaste' => 0.05,
            'batteries' => 0.1,
            'electronics' => 0.12,
            'tetrapak' => 0.1,
        ];

        // Emission factors - Landfill Avoidance EF
        $landfillAvoidanceEFFactors = [
            'paper' => 0.78,
            'plasticPPHD' => 0.08,
            'plasticPS' => 0.05,
            'plasticLDPE' => 0.06,
            'aluminium' => 9,
            'steel' => 2,
            'glass' => 0.03,
            'foodWaste' => 0.7,
            'gardenWaste' => 0.5,
            'batteries' => 1.5,
            'electronics' => 1,
            'tetrapak' => 0.25,
        ];

        // Other Offsets factors
        $otherOffsetsFactors = [
            'paper' => 15,
            'plasticPPHD' => 20,
            'plasticPS' => 22,
            'plasticLDPE' => 25,
            'aluminium' => 200,
            'steel' => 45,
            'glass' => 5,
            'foodWaste' => 10,
            'gardenWaste' => 8,
            'batteries' => 30,
            'electronics' => 25,
            'tetrapak' => 5,
        ];

        $totalCO2 = 0;
        foreach ($weights as $key => $weight) {
            $scope3EF = $weight * ($scope3EFFactors[$key] ?? 0);
            $landfillAvoidanceEF = $weight * ($landfillAvoidanceEFFactors[$key] ?? 0);
            $otherOffsets = $weight * (($otherOffsetsFactors[$key] ?? 0) / 25);
            $totalCO2 += $scope3EF + $landfillAvoidanceEF + $otherOffsets;
        }

        return round($totalCO2, 2);
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
            ],
        ];
    }
}
