<?php

namespace App\Http\Controllers;

use App\Services\ChartImageService;
use App\Models\Company;
use App\Traits\ScopeByClientTrait;
use App\Models\Branch;
use App\Models\Site;
use App\Models\OrderWasteStream;
use App\Models\ClientMonthlyMaterialSummary;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportController extends Controller
{
    use ScopeByClientTrait;

    protected $chartService;

    public function __construct(ChartImageService $chartService)
    {
        $this->chartService = $chartService;
    }

    /**
     * Display the waste management report (HTML view)
     */
    public function wasteManagement(Request $request)
    {
        $companyId = $request->input('company_id');
        $branchId = $request->input('branch_id');
        $siteId = $request->input('site_id');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        // If no filters provided, show the filter form
        if (!$companyId && !$branchId && !$siteId) {
            $companies = $this->scopeCompaniesForUser();

            return Inertia::render('Reports/WasteManagement', [
                'companies' => $companies,
                'filters' => [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'site_id' => $siteId,
                    'month' => $month,
                    'year' => $year,
                ],
            ]);
        }

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId ? (int) $companyId : null, $branchId ? (int) $branchId : null, $siteId ? (int) $siteId : null);

        // Get the company, branch, and site objects
        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::find($branchId) : null;
        $site = $siteId ? Site::find($siteId) : null;

        $reportData = $this->getReportData($company, $branch, $site, (int) $month, (int) $year);
        $chartPaths = $this->generateCharts($reportData);

        return view('reports.waste-management', [
            'reportData' => $reportData,
            'chartPaths' => $chartPaths,
        ]);
    }

    /**
     * Generate PDF of waste management report
     */
    public function wasteManagementPdf(Request $request)
    {
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        // Get the company, branch, and site objects
        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::find($branchId) : null;
        $site = $siteId ? Site::find($siteId) : null;

        $reportData = $this->getReportData($company, $branch, $site, (int) $month, (int) $year);
        $chartPaths = $this->generateCharts($reportData);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $html = view('reports.waste-management-pdf', [
            'reportData' => $reportData,
            'chartPaths' => $chartPaths,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Waste_Management_Report_' . $reportData['reportDate'] . '.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Display report data summary (JSON dump for testing)
     */
    public function wasteManagementSummary(Request $request)
    {
        $companies = $this->scopeCompaniesForUser();

        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;
        $branchId = $request->input('branch_id') ? (int) $request->input('branch_id') : null;
        $siteId = $request->input('site_id') ? (int) $request->input('site_id') : null;
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        [$companyId, $branchId, $siteId] = $this->enforceCompanyScope($companyId, $branchId, $siteId);

        $company = $companyId ? Company::find($companyId) : null;
        $branch = $branchId ? Branch::find($branchId) : null;
        $site = $siteId ? Site::find($siteId) : null;

        $reportData = $this->getReportData($company, $branch, $site, (int) $month, (int) $year);

        return Inertia::render('Reports/WasteManagementSummary', [
            'reportData' => $reportData,
            'companies' => $companies,
            'filters' => [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'site_id' => $siteId,
                'month' => $month,
                'year' => $year,
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
     * Get report data for waste management report
     */
    private function getReportData(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null): array
    {
        // Determine the company name for display
        $companyName = 'XXXX';
        if ($site && $site->branch && $site->branch->company) {
            $companyName = $site->branch->company->name;
        } elseif ($branch && $branch->company) {
            $companyName = $branch->company->name;
        } elseif ($company) {
            $companyName = $company->name;
        }

        // Format report date (e.g., "Aug-25" for August 2025)
        $reportDate = 'XXXX';
        if ($month && $year) {
            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $monthName = $monthNames[$month - 1] ?? 'XXX';
            $yearShort = substr($year, -2);
            $reportDate = $monthName . '-' . $yearShort;
        }

        // Get material weights from finalized orders
        $materialWeights = $this->getMaterialWeights($company, $branch, $site, $month, $year);
        $grades = $this->getGrades($company, $branch, $site, $month, $year);
        $recyclingCommodities = $this->getRecyclingCommodities($materialWeights, 1);
        $recyclingCommodities2 = $this->getRecyclingCommodities($materialWeights, 2);

        // Calculate recyclingRecovered = sum of all recycling weights
        $recyclingRecovered = 0;
        foreach ($recyclingCommodities as $commodity) {
            $recyclingRecovered += (float) $commodity['qty'];
        }
        foreach ($recyclingCommodities2 as $commodity) {
            $recyclingRecovered += (float) $commodity['qty'];
        }
        $recyclingRecovered = round($recyclingRecovered, 2);

        // Get organicsRecovered from grades
        $organicsRecovered = $grades['organicsRecovered'];

        // Calculate totalIncomingWaste = generalWaste + organicsRecovered + recyclingRecovered
        $totalIncomingWaste = round($grades['generalWaste'] + $organicsRecovered + $recyclingRecovered, 2);

        // Calculate divertedFromLandfill = (recyclingRecovered + organicsRecovered) / totalIncomingWaste * 100
        $divertedFromLandfill = 0;
        if ($totalIncomingWaste > 0) {
            $divertedFromLandfill = round(($recyclingRecovered + $organicsRecovered) / $totalIncomingWaste * 100, 2);
        }

        // Calculate landfill space saved breakdown
        $landfillSpaceSavedData = $this->getLandfillSpaceSaved($company, $branch, $site, $month, $year, $organicsRecovered);
        $landfillSpaceSaved = $landfillSpaceSavedData['total'];

        // Calculate materials CO2e
        $materialsCO2eData = $this->getMaterialsCO2e($company, $branch, $site, $month, $year, $organicsRecovered);
        $materialsCO2e = $materialsCO2eData['materials'];
        $materialsCO2eTotals = $materialsCO2eData['totals'];

        // Calculate environmental impact
        $environmentalImpact = $this->getEnvironmentalImpact($company, $branch, $site, $month, $year, $organicsRecovered);

        return [
            'companyName' => $companyName,
            'reportDate' => $reportDate,
            'environmentalImpact' => $environmentalImpact,
            'grades' => $grades,
            'recyclingCommodities' => $recyclingCommodities,
            'recyclingCommodities2' => $recyclingCommodities2,
            'summary' => [
                'recyclingRecovered' => $recyclingRecovered,
                'organicsRecovered' => $organicsRecovered,
                'totalIncomingWaste' => $totalIncomingWaste,
                'divertedFromLandfill' => $divertedFromLandfill,
                'landfillSpaceSaved' => $landfillSpaceSaved,
                'lifecycleSaving' => $materialsCO2eTotals['lifecycleSaving'],
            ],
            'landfillSpaceSavedBreakdown' => $landfillSpaceSavedData,
            'materialsCO2e' => $materialsCO2e,
            'materialsCO2eTotals' => $materialsCO2eTotals,
            'carbonEmissionsAvoided' => $this->calculateCarbonEmissionsAvoided($materialsCO2eTotals),
            'cumulativeImpact' => $this->calculateCumulativeImpact($environmentalImpact, $materialsCO2eTotals),
            'recyclingBreakdown' => $this->calculateRecyclingBreakdown($company, $branch, $site, $month, $year, $organicsRecovered, $recyclingRecovered),
        ];
    }

    /**
     * Get summaries query builder for the given filters
     */
    private function getSummariesQuery(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null)
    {
        $query = ClientMonthlyMaterialSummary::query()
            ->where('year', $year)
            ->where('month', $month);

        // Filter by site (most specific) - only this site
        if ($site) {
            $query->where('site_id', $site->id);
        }
        // Filter by branch - all sites under this branch (both branch-level and site-level summaries)
        elseif ($branch) {
            $query->where('branch_id', $branch->id);
            // Include both site-level (site_id not null) and branch-level (site_id null) summaries
        }
        // Filter by company - all branches and sites under this company
        elseif ($company) {
            $query->where('company_id', $company->id);
            // Include company-level, branch-level, and site-level summaries
        }
        // No filters - get all companies (aggregate all summaries)
        // No additional where clause needed

        return $query;
    }

    /**
     * Get material-level summaries with material relationships loaded
     */
    private function getMaterialSummaries(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null)
    {
        if ((!$company && !$branch && !$site) || !$month || !$year) {
            return collect([]);
        }

        return $this->getSummariesQuery($company, $branch, $site, $month, $year)
            ->whereNotNull('material_id')
            ->with(['material.grade', 'material.wasteStream'])
            ->get();
    }

    /**
     * Get category-level (waste stream) summaries
     */
    private function getCategorySummaries(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null)
    {
        if ((!$company && !$branch && !$site) || !$month || !$year) {
            return collect([]);
        }

        return $this->getSummariesQuery($company, $branch, $site, $month, $year)
            ->whereNotNull('waste_stream_id')
            ->with('wasteStream')
            ->get();
    }

    /**
     * Build order query filter based on company, branch, and site
     */
    private function buildOrderFilter($query, ?Company $company = null, ?Branch $branch = null, ?Site $site = null, $startDate, $endDate)
    {
        $query->where('status', 'finalized')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('actual_collection_date', [$startDate, $endDate])
                    ->orWhere(function ($subQ) use ($startDate, $endDate) {
                        $subQ->whereNull('actual_collection_date')
                            ->whereBetween('requested_collection_date', [$startDate, $endDate]);
                    });
            });

        // Filter by site (most specific)
        if ($site) {
            $query->where('site_id', $site->id);
        }
        // Filter by branch (if no site selected)
        elseif ($branch) {
            $query->where('branch_id', $branch->id);
        }
        // Filter by company (if no branch or site selected)
        elseif ($company) {
            $query->where('company_id', $company->id);
        }
    }

    /**
     * Get grades (generalWaste, nonCompactableWaste, hazardousWaste, organicsRecovered) from pre-calculated summaries
     */
    private function getGrades(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null): array
    {
        if ((!$company && !$branch && !$site) || !$month || !$year) {
            return [
                'generalWaste' => 0,
                'nonCompactableWaste' => 0,
                'hazardousWaste' => 0,
                'organicsRecovered' => 0,
            ];
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        $grades = [
            'generalWaste' => 0,
            'nonCompactableWaste' => 0,
            'hazardousWaste' => 0,
            'organicsRecovered' => 0,
        ];

        foreach ($summaries as $summary) {
            if (!$summary->material || !$summary->material->wasteStream || !$summary->material->grade) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            // General Waste: Waste stream = "Waste", Grade = "General Waste"
            if ($wasteStreamName === 'Waste' && $gradeName === 'General Waste') {
                $grades['generalWaste'] += $weight;
            }
            // Non Compactable Waste: Waste stream = "Waste", Grade = "Non Compactable Waste"
            elseif ($wasteStreamName === 'Waste' && $gradeName === 'Non Compactable Waste') {
                $grades['nonCompactableWaste'] += $weight;
            }
            // Hazardous Waste: Any material with Waste stream = "Hazardous Waste"
            elseif ($wasteStreamName === 'Hazardous Waste') {
                $grades['hazardousWaste'] += $weight;
            }
            // Organics Recovered: Waste stream = "Organic Waste", Grade = "Organics Recovered"
            elseif ($wasteStreamName === 'Organic Waste' && $gradeName === 'Organics Recovered') {
                $grades['organicsRecovered'] += $weight;
            }
        }

        // Round to 2 decimal places
        return [
            'generalWaste' => round($grades['generalWaste'], 2),
            'nonCompactableWaste' => round($grades['nonCompactableWaste'], 2),
            'hazardousWaste' => round($grades['hazardousWaste'], 2),
            'organicsRecovered' => round($grades['organicsRecovered'], 2),
        ];
    }

    /**
     * Get material weights from pre-calculated summaries for a company/branch/site in a given month
     */
    private function getMaterialWeights(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null): array
    {
        if ((!$company && !$branch && !$site) || !$month || !$year) {
            return [];
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        // Group by grade name and sum total_weight
        $materialWeights = [];
        foreach ($summaries as $summary) {
            if (!$summary->material || !$summary->material->grade) {
                continue;
            }

            $gradeName = $summary->material->grade->name;
            if (!isset($materialWeights[$gradeName])) {
                $materialWeights[$gradeName] = 0;
            }
            $materialWeights[$gradeName] += (float) $summary->total_weight;
        }

        return $materialWeights;
    }

    /**
     * Get recycling commodities array with actual weights
     */
    private function getRecyclingCommodities(array $materialWeights, int $set = 1): array
    {
        $commodities1 = [
            'Alu Cans', 'Alu Foil', 'BOPP', 'CMW', 'CMW Rolls', 'EPS/XPS', 'FN/SBM', 'Glass', 'Hangers',
            'HD', 'HD - Colour', 'HD - PP', 'HD Clear', 'HD Crates', 'HD Dark', 'HD Light', 'HD White',
            'Heavy Steel', 'HL 1', 'HL Books', 'HL Dirty',
        ];

        $commodities2 = [
            'K4', 'K4 Rolls', 'Label Backing', 'LD Clear', 'LD Consul', 'LD Mix', 'Light Steel',
            'Light Steel Cans', 'Light Steel Drums', 'Mixed Bag', 'Oil', 'PET Clear', 'PET Mix',
            'Pet Strapping', 'PP', 'PP Caps', 'Tetrapak', 'Tissue Paper', 'Wrapping', 'XPS', '',
        ];

        $commodities = $set === 1 ? $commodities1 : $commodities2;
        $result = [];

        foreach ($commodities as $name) {
            $weight = isset($materialWeights[$name]) ? round($materialWeights[$name], 2) : 0;
            $result[] = ['name' => $name, 'qty' => $weight];
        }

        return $result;
    }

    /**
     * Calculate landfill space saved breakdown from pre-calculated summaries
     */
    private function getLandfillSpaceSaved(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null, float $organicsRecovered = 0): array
    {
        if ((!$company && !$branch && !$site) || !$month || !$year) {
            return [
                'tetrapak' => ['total' => 0, 'spaceSaved' => 0],
                'plastics' => ['total' => 0, 'spaceSaved' => 0],
                'paper' => ['total' => 0, 'spaceSaved' => 0],
                'glass' => ['total' => 0, 'spaceSaved' => 0],
                'metal' => ['total' => 0, 'spaceSaved' => 0],
                'foodWaste' => ['total' => 0, 'spaceSaved' => 0],
                'total' => 0,
            ];
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        $totals = [
            'tetrapak' => 0,
            'plastics' => 0,
            'paper' => 0,
            'glass' => 0,
            'metal' => 0,
        ];

        foreach ($summaries as $summary) {
            if (!$summary->material || !$summary->material->grade || !$summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            // Tetrapak: grade name = "Tetrapak"
            if ($gradeName === 'Tetrapak') {
                $totals['tetrapak'] += $weight;
            }
            // Plastics: waste stream = "Plastic"
            elseif ($wasteStreamName === 'Plastic') {
                $totals['plastics'] += $weight;
            }
            // Paper: waste stream = "Paper"
            elseif ($wasteStreamName === 'Paper') {
                $totals['paper'] += $weight;
            }
            // Glass: grade name = "Glass"
            elseif ($gradeName === 'Glass') {
                $totals['glass'] += $weight;
            }
            // Metal: Heavy Steel, Light Steel, Light Steel Cans, Light Steel Drums
            elseif (in_array($gradeName, ['Heavy Steel', 'Light Steel', 'Light Steel Cans', 'Light Steel Drums'])) {
                $totals['metal'] += $weight;
            }
        }

        // Calculate landfill space saved for each category
        $breakdown = [
            'tetrapak' => [
                'total' => round($totals['tetrapak'], 2),
                'spaceSaved' => round($totals['tetrapak'] / 200, 2),
            ],
            'plastics' => [
                'total' => round($totals['plastics'], 2),
                'spaceSaved' => round($totals['plastics'] / 150, 2),
            ],
            'paper' => [
                'total' => round($totals['paper'], 2),
                'spaceSaved' => round($totals['paper'] / 300, 2),
            ],
            'glass' => [
                'total' => round($totals['glass'], 2),
                'spaceSaved' => round($totals['glass'] / 450, 2),
            ],
            'metal' => [
                'total' => round($totals['metal'], 2),
                'spaceSaved' => round($totals['metal'] / 500, 2),
            ],
            'foodWaste' => [
                'total' => round($organicsRecovered, 2),
                'spaceSaved' => round($organicsRecovered / 350, 2),
            ],
        ];

        // Calculate total landfill space saved
        $totalSpaceSaved = 0;
        foreach ($breakdown as $category) {
            $totalSpaceSaved += $category['spaceSaved'];
        }

        $breakdown['total'] = round($totalSpaceSaved, 2);

        return $breakdown;
    }

    /**
     * Calculate materials CO2e with weights and emission factors from pre-calculated summaries
     */
    private function getMaterialsCO2e(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null, float $organicsRecovered = 0): array
    {
        if ((!$company && !$branch && !$site) || !$month || !$year) {
            return $this->getEmptyMaterialsCO2e();
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        // Initialize totals
        $weights = [
            'paper' => 0,
            'plasticPPHD' => 0,
            'plasticPS' => 0,
            'plasticLDPE' => 0,
            'aluminium' => 0,
            'steel' => 0,
            'glass' => 0,
            'foodWaste' => $organicsRecovered, // Use organics recovered for food waste
            'gardenWaste' => 0,
            'batteries' => 0,
            'electronics' => 0,
            'tetrapak' => 0,
        ];

        foreach ($summaries as $summary) {
            if (!$summary->material || !$summary->material->grade || !$summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            // Tetrapak: grade name = "Tetrapak" (separate from paper)
            if ($gradeName === 'Tetrapak') {
                $weights['tetrapak'] += $weight;
            }
            // Paper: waste stream = "Paper" (excluding Tetrapak)
            elseif ($wasteStreamName === 'Paper' && $gradeName !== 'Tetrapak') {
                $weights['paper'] += $weight;
            }
            // Plastic PP / HD: HD grades and PP grades
            elseif ($wasteStreamName === 'Plastic' && (
                strpos($gradeName, 'HD') === 0 || 
                $gradeName === 'PP' || 
                $gradeName === 'PP Caps'
            )) {
                $weights['plasticPPHD'] += $weight;
            }
            // Plastic PS (Polystyrene): EPS/XPS
            elseif ($wasteStreamName === 'Plastic' && ($gradeName === 'EPS/XPS' || $gradeName === 'XPS')) {
                $weights['plasticPS'] += $weight;
            }
            // Plastic LDPE Film: LD grades
            elseif ($wasteStreamName === 'Plastic' && strpos($gradeName, 'LD') === 0) {
                $weights['plasticLDPE'] += $weight;
            }
            // Aluminium: waste stream = "Aluminium"
            elseif ($wasteStreamName === 'Aluminium') {
                $weights['aluminium'] += $weight;
            }
            // Steel: waste stream = "Metal" with steel grades
            elseif ($wasteStreamName === 'Metal' && (
                $gradeName === 'Heavy Steel' || 
                $gradeName === 'Light Steel' || 
                $gradeName === 'Light Steel Cans' || 
                $gradeName === 'Light Steel Drums'
            )) {
                $weights['steel'] += $weight;
            }
            // Glass: waste stream = "Glass"
            elseif ($wasteStreamName === 'Glass') {
                $weights['glass'] += $weight;
            }
            // Garden Waste: waste stream = "Garden Waste"
            elseif ($wasteStreamName === 'Garden Waste') {
                $weights['gardenWaste'] += $weight;
            }
            // Batteries: waste stream = "Batteries" (if it exists)
            elseif ($wasteStreamName === 'Batteries') {
                $weights['batteries'] += $weight;
            }
            // Electronics (E-waste): waste stream = "Electronics" or "E-waste" (if it exists)
            elseif (in_array($wasteStreamName, ['Electronics', 'E-waste', 'Electronics (E-waste)'])) {
                $weights['electronics'] += $weight;
            }
        }

        // Emission factors - Scope 3 EF (orange column)
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

        // Emission factors - Landfill Avoidance EF (green column)
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

        // Other Offsets factors (yellow column)
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

        // Material name to key mapping
        $materialKeyMap = [
            'Paper' => 'paper',
            'Plastic PP / HD' => 'plasticPPHD',
            'Plastic PS (Polystyrene)' => 'plasticPS',
            'Plastic LDPE Film' => 'plasticLDPE',
            'Aluminium' => 'aluminium',
            'Steel' => 'steel',
            'Glass' => 'glass',
            'Food Waste' => 'foodWaste',
            'Garden Waste' => 'gardenWaste',
            'Batteries' => 'batteries',
            'Electronics (E-waste)' => 'electronics',
            'Tetrapak' => 'tetrapak',
        ];

        // Initialize totals
        $totals = [
            'scope3EF' => 0,
            'landfillAvoidanceEF' => 0,
            'otherOffsets' => 0,
            'lifecycleSaving' => 0,
        ];

        // Build materials CO2e array and calculate values
        // Use raw weights for calculations, round to whole number for display
        $materials = [];
        $materialOrder = [
            'Paper' => 'paper',
            'Plastic PP / HD' => 'plasticPPHD',
            'Plastic PS (Polystyrene)' => 'plasticPS',
            'Plastic LDPE Film' => 'plasticLDPE',
            'Aluminium' => 'aluminium',
            'Steel' => 'steel',
            'Glass' => 'glass',
            'Food Waste' => 'foodWaste',
            'Garden Waste' => 'gardenWaste',
            'Batteries' => 'batteries',
            'Electronics (E-waste)' => 'electronics',
            'Tetrapak' => 'tetrapak',
        ];

        foreach ($materialOrder as $materialName => $key) {
            // Use raw weight for calculations
            $rawWeight = $weights[$key];
            // Round weight to whole number for display (cast to int to ensure no decimals)
            $displayWeight = (int) round($rawWeight, 0);

            // Calculate using raw weight for accuracy
            $scope3EF = $rawWeight * $scope3EFFactors[$key];
            $landfillAvoidanceEF = $rawWeight * $landfillAvoidanceEFFactors[$key];
            $otherOffsets = $rawWeight * ($otherOffsetsFactors[$key] / 25);
            $lifecycleSaving = $scope3EF + $landfillAvoidanceEF + $otherOffsets;

            // Round calculated values to 2 decimal places
            $scope3EF = round($scope3EF, 2);
            $landfillAvoidanceEF = round($landfillAvoidanceEF, 2);
            $otherOffsets = round($otherOffsets, 2);
            $lifecycleSaving = round($lifecycleSaving, 2);

            // Add to totals
            $totals['scope3EF'] += $scope3EF;
            $totals['landfillAvoidanceEF'] += $landfillAvoidanceEF;
            $totals['otherOffsets'] += $otherOffsets;
            $totals['lifecycleSaving'] += $lifecycleSaving;

            $materials[] = [
                'material' => $materialName,
                'weight' => $displayWeight,
                'scope3EF' => $scope3EF,
                'landfillAvoidanceEF' => $landfillAvoidanceEF,
                'otherOffsets' => $otherOffsets,
                'lifecycleSaving' => $lifecycleSaving,
            ];
        }

        // Round totals to 2 decimal places
        $totals['scope3EF'] = round($totals['scope3EF'], 2);
        $totals['landfillAvoidanceEF'] = round($totals['landfillAvoidanceEF'], 2);
        $totals['otherOffsets'] = round($totals['otherOffsets'], 2);
        $totals['lifecycleSaving'] = round($totals['lifecycleSaving'], 2);

        return [
            'materials' => $materials,
            'totals' => $totals,
        ];
    }

    /**
     * Get empty materials CO2e array for when no data is available
     */
    private function getEmptyMaterialsCO2e(): array
    {
        $materials = [
            ['material' => 'Paper', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Plastic PP / HD', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Plastic PS (Polystyrene)', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Plastic LDPE Film', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Aluminium', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Steel', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Glass', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Food Waste', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Garden Waste', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Batteries', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Electronics (E-waste)', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
            ['material' => 'Tetrapak', 'weight' => 0, 'scope3EF' => 0, 'landfillAvoidanceEF' => 0, 'otherOffsets' => 0, 'lifecycleSaving' => 0],
        ];

        return [
            'materials' => $materials,
            'totals' => [
                'scope3EF' => 0,
                'landfillAvoidanceEF' => 0,
                'otherOffsets' => 0,
                'lifecycleSaving' => 0,
            ],
        ];
    }

    /**
     * Calculate environmental impact (trees saved, energy saved, water saved) from pre-calculated summaries
     */
    private function getEnvironmentalImpact(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null, float $organicsRecovered = 0): array
    {
        if ((!$company && !$branch && !$site) || !$month || !$year) {
            return [
                'treesSaved' => 0,
                'energySaved' => 0,
                'waterSaved' => 0,
            ];
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        // Initialize category weights
        $categoryWeights = [
            'paper' => 0,
            'plastics' => 0,
            'aluminium' => 0,
            'organics' => $organicsRecovered,
            'tetrapak' => 0,
            'steel' => 0,
            'glass' => 0,
        ];

        foreach ($summaries as $summary) {
            if (!$summary->material || !$summary->material->grade || !$summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            // Tetrapak: grade name = "Tetrapak" (separate from paper)
            if ($gradeName === 'Tetrapak') {
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

        // Energy Saved factors (blue column)
        $energyFactors = [
            'paper' => 10,
            'plastics' => 20,
            'aluminium' => 140,
            'organics' => 9,
            'tetrapak' => 2,
            'steel' => 15,
            'glass' => 7,
        ];

        // Water Saved factors (green column)
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

        return [
            'treesSaved' => $treesSaved,
            'energySaved' => $energySaved,
            'waterSaved' => $waterSaved,
        ];
    }

    /**
     * Calculate carbon emissions avoided
     * TODO: Confirm calculation formula - currently using lifecycleSaving converted to km
     */
    private function calculateCarbonEmissionsAvoided(array $materialsCO2eTotals): float
    {
        // Convert lifecycleSaving (kg CO₂e) to km
        // Assuming 1 kg CO₂e = ~0.17 km (this may need adjustment based on requirements)
        $lifecycleSaving = $materialsCO2eTotals['lifecycleSaving'] ?? 0;
        return round($lifecycleSaving * 0.17, 2);
    }

    /**
     * Calculate cumulative impact percentages for dashboard
     */
    private function calculateCumulativeImpact(array $environmentalImpact, array $materialsCO2eTotals): array
    {
        $waterSaved = $environmentalImpact['waterSaved'] ?? 0;
        $energySaved = $environmentalImpact['energySaved'] ?? 0;
        $lifecycleSaving = $materialsCO2eTotals['lifecycleSaving'] ?? 0;

        // TODO: Confirm if these should be percentages or actual values
        // For now, returning as percentages (may need adjustment)
        return [
            ['name' => 'Water Saved', 'value' => round($waterSaved, 2), 'color' => '#3b82f6'],
            ['name' => 'Energy Saved', 'value' => round($energySaved, 2), 'color' => '#a3e635'],
            ['name' => 'Lifecycle Saving CO₂e (kg)', 'value' => round($lifecycleSaving, 2), 'color' => '#6b7280'],
        ];
    }

    /**
     * Calculate recycling breakdown percentages from pre-calculated summaries
     */
    private function calculateRecyclingBreakdown(?Company $company = null, ?Branch $branch = null, ?Site $site = null, ?int $month = null, ?int $year = null, float $organicsRecovered = 0, float $recyclingRecovered = 0): array
    {
        if ((!$company && !$branch && !$site) || !$month || !$year || $recyclingRecovered == 0) {
            return [
                ['name' => 'Paper', 'value' => 0, 'color' => '#60a5fa'],
                ['name' => 'Plastics', 'value' => 0, 'color' => '#a3e635'],
                ['name' => 'Aluminium', 'value' => 0, 'color' => '#4b5563'],
                ['name' => 'Organics', 'value' => 0, 'color' => '#fbbf24'],
                ['name' => 'Tetrapak', 'value' => 0, 'color' => '#fde047'],
                ['name' => 'Steel', 'value' => 0, 'color' => '#e5e7eb'],
                ['name' => 'Glass', 'value' => 0, 'color' => '#3b82f6'],
            ];
        }

        // Get material-level summaries
        $summaries = $this->getMaterialSummaries($company, $branch, $site, $month, $year);

        // Initialize category weights
        $categoryWeights = [
            'paper' => 0,
            'plastics' => 0,
            'aluminium' => 0,
            'organics' => $organicsRecovered,
            'tetrapak' => 0,
            'steel' => 0,
            'glass' => 0,
        ];

        foreach ($summaries as $summary) {
            if (!$summary->material || !$summary->material->grade || !$summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            // Tetrapak: grade name = "Tetrapak" (separate from paper)
            if ($gradeName === 'Tetrapak') {
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

        // Calculate total for percentage calculation
        $totalWeight = array_sum($categoryWeights);
        
        // Calculate percentages
        $breakdown = [];
        if ($totalWeight > 0) {
            $breakdown = [
                ['name' => 'Paper', 'value' => round(($categoryWeights['paper'] / $totalWeight) * 100, 2), 'color' => '#60a5fa'],
                ['name' => 'Plastics', 'value' => round(($categoryWeights['plastics'] / $totalWeight) * 100, 2), 'color' => '#a3e635'],
                ['name' => 'Aluminium', 'value' => round(($categoryWeights['aluminium'] / $totalWeight) * 100, 2), 'color' => '#4b5563'],
                ['name' => 'Organics', 'value' => round(($categoryWeights['organics'] / $totalWeight) * 100, 2), 'color' => '#fbbf24'],
                ['name' => 'Tetrapak', 'value' => round(($categoryWeights['tetrapak'] / $totalWeight) * 100, 2), 'color' => '#fde047'],
                ['name' => 'Steel', 'value' => round(($categoryWeights['steel'] / $totalWeight) * 100, 2), 'color' => '#e5e7eb'],
                ['name' => 'Glass', 'value' => round(($categoryWeights['glass'] / $totalWeight) * 100, 2), 'color' => '#3b82f6'],
            ];
        } else {
            $breakdown = [
                ['name' => 'Paper', 'value' => 0, 'color' => '#60a5fa'],
                ['name' => 'Plastics', 'value' => 0, 'color' => '#a3e635'],
                ['name' => 'Aluminium', 'value' => 0, 'color' => '#4b5563'],
                ['name' => 'Organics', 'value' => 0, 'color' => '#fbbf24'],
                ['name' => 'Tetrapak', 'value' => 0, 'color' => '#fde047'],
                ['name' => 'Steel', 'value' => 0, 'color' => '#e5e7eb'],
                ['name' => 'Glass', 'value' => 0, 'color' => '#3b82f6'],
            ];
        }

        return $breakdown;
    }

    /**
     * Generate all charts for the report
     */
    private function generateCharts(array $reportData): array
    {
        $timestamp = now()->format('YmdHis');
        $chartPaths = [];

        // Ensure storage link exists
        if (!\Storage::disk('public')->exists('charts')) {
            \Storage::disk('public')->makeDirectory('charts');
        }

        // Page 1: Pie chart (Waste Distribution)
        $pieData = [
            ['name' => 'General Waste', 'value' => $reportData['grades']['generalWaste'], 'color' => '#1e3a5f'],
            ['name' => 'Non Compactable Waste', 'value' => max(1, $reportData['grades']['nonCompactableWaste']), 'color' => '#5ba3c0'],
            ['name' => 'Hazardous Waste', 'value' => max(1, $reportData['grades']['hazardousWaste']), 'color' => '#dc2626'],
            ['name' => 'Organics Recovered', 'value' => max(1, $reportData['grades']['organicsRecovered']), 'color' => '#a3e635'],
            ['name' => 'Recycling Recovered', 'value' => $reportData['summary']['recyclingRecovered'], 'color' => '#3b82f6'],
        ];

        $chartPaths['page1_pie'] = $this->chartService->generatePieChart([
            'title' => '',
            'labels' => array_column($pieData, 'name'),
            'data' => array_column($pieData, 'value'),
            'colors' => array_column($pieData, 'color'),
            'legendPosition' => 'bottom',
            'width' => 900,
            'height' => 600,
            'options' => [
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                        'align' => 'center',
                        'labels' => [
                            'boxWidth' => 12,
                            'boxHeight' => 12,
                            'padding' => 12,
                            'font' => [
                                'size' => 11,
                            ],
                        ],
                    ],
                ],
            ],
        ], "page1_pie_{$timestamp}.png");

        // Page 3: Stacked Bar Chart (Horizontal)
        $materialsCO2eTotals = $reportData['materialsCO2eTotals'] ?? [];
        $stackedBarData = [
            'scope3EF' => $materialsCO2eTotals['scope3EF'] ?? 0,
            'landfillAvoidanceEF' => $materialsCO2eTotals['landfillAvoidanceEF'] ?? 0,
            'otherOffsets' => $materialsCO2eTotals['otherOffsets'] ?? 0,
            'lifecycleSaving' => $materialsCO2eTotals['lifecycleSaving'] ?? 0,
        ];

        $chartPaths['page3_stacked'] = $this->chartService->generateStackedBarChart([
            'title' => '(kg CO₂e)',
            'labels' => ['Total'],
            'horizontal' => true, // Horizontal bar chart
            'datasets' => [
                [
                    'label' => 'Scope 3 EF (kg CO₂e/kg)²',
                    'data' => [$stackedBarData['scope3EF']],
                    'backgroundColor' => '#60a5fa',
                ],
                [
                    'label' => 'Landfill Avoidance EF (kg CO₂e/kg)³',
                    'data' => [$stackedBarData['landfillAvoidanceEF']],
                    'backgroundColor' => '#9ca3af',
                ],
                [
                    'label' => 'Other Offsets (kg CO₂e)',
                    'data' => [$stackedBarData['otherOffsets']],
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label' => 'Lifecycle Saving (kg CO₂e)',
                    'data' => [$stackedBarData['lifecycleSaving']],
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'options' => [
                'scales' => [
                    'x' => [
                        'max' => 3000,
                        'ticks' => [
                            'stepSize' => 500,
                        ],
                    ],
                ],
            ],
            'width' => 900,
            'height' => 400,
        ], "page3_stacked_{$timestamp}.png");

        // Page 3: Single Bar Chart
        $carbonEmissionsAvoided = $reportData['carbonEmissionsAvoided'] ?? 0;
        $maxCarbonValue = max($carbonEmissionsAvoided * 1.1, 18000); // Add 10% padding, minimum 18000
        
        $chartPaths['page3_single'] = $this->chartService->generateBarChart([
            'title' => 'Total Carbon Emissions Avoided in KM',
            'labels' => ['1'],
            'datasets' => [[
                'label' => 'Carbon Emissions Avoided',
                'data' => [$carbonEmissionsAvoided],
                'backgroundColor' => '#60a5fa',
            ]],
            'options' => [
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'max' => $maxCarbonValue,
                        'ticks' => [
                            'stepSize' => round($maxCarbonValue / 9, 0),
                        ],
                    ],
                ],
            ],
            'width' => 900,
            'height' => 300,
        ], "page3_single_{$timestamp}.png");

        // Page 4: Cumulative Impact Doughnut
        $chartPaths['page4_cumulative'] = $this->chartService->generateDoughnutChart([
            'title' => 'CUMULATIVE IMPACT DASHBOARD',
            'labels' => array_column($reportData['cumulativeImpact'], 'name'),
            'data' => array_column($reportData['cumulativeImpact'], 'value'),
            'colors' => array_column($reportData['cumulativeImpact'], 'color'),
            'legendPosition' => 'top',
            'cutout' => '60%',
            'width' => 900,
            'height' => 400,
        ], "page4_cumulative_{$timestamp}.png");

        // Page 4: Recycling Breakdown Doughnut
        $chartPaths['page4_recycling'] = $this->chartService->generateDoughnutChart([
            'title' => 'RECYCLING BREAKDOWN',
            'labels' => array_column($reportData['recyclingBreakdown'], 'name'),
            'data' => array_column($reportData['recyclingBreakdown'], 'value'),
            'colors' => array_column($reportData['recyclingBreakdown'], 'color'),
            'legendPosition' => 'bottom',
            'cutout' => '60%',
            'width' => 900,
            'height' => 400,
        ], "page4_recycling_{$timestamp}.png");

        // Page 5: Waste vs Recovery Pie
        $wasteVsRecovery = [
            ['name' => 'Waste', 'value' => 15, 'color' => '#1e3a5f'],
            ['name' => 'Recovery', 'value' => 85, 'color' => '#3b82f6'],
        ];

        $chartPaths['page5_waste_recovery'] = $this->chartService->generatePieChart([
            'title' => 'WASTE vs RECOVERY',
            'labels' => array_column($wasteVsRecovery, 'name'),
            'data' => array_column($wasteVsRecovery, 'value'),
            'colors' => array_column($wasteVsRecovery, 'color'),
            'legendPosition' => 'bottom',
            'width' => 900,
            'height' => 400,
        ], "page5_waste_recovery_{$timestamp}.png");

        return $chartPaths;
    }
}
