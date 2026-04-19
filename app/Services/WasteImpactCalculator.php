<?php

namespace App\Services;

/**
 * Single source of truth for environmental impact calculations used by:
 * - Dashboard (Lifecycle Carbon Avoidance, Water Saved, Trees Saved, Energy Saved and charts)
 * - Waste management summary page (reference)
 * - Waste management report (HTML and PDF)
 *
 * Water saved (L, then kL) is delegated to {@see WaterCalculator} (docs/Water Calculator.xlsx).
 * Carbon equivalency metrics from lifecycle CO₂e: {@see LifecycleCarbonEquivalency} (docs/Dashboard & Reports - Metrics).
 */
class WasteImpactCalculator
{
    public function __construct(
        private readonly WaterCalculator $waterCalculator = new WaterCalculator,
        private readonly LifecycleCarbonEquivalency $lifecycleCarbonEquivalency = new LifecycleCarbonEquivalency,
    ) {}

    /** Trees saved per kg of paper recycled (20 trees per tonne = 20/1000 per kg) */
    private const TREES_PER_KG_PAPER = 20 / 1000;

    /** Energy saved factors (per kg) by category */
    private const ENERGY_FACTORS = [
        'paper' => 10,
        'plastics' => 20,
        'aluminium' => 140,
        'organics' => 9,
        'tetrapak' => 2,
        'steel' => 15,
        'glass' => 7,
        'wood' => 8,
    ];

    /** Scope 3 emission factors (kg CO₂e per kg) for lifecycle carbon calculation */
    private const SCOPE3_EF_FACTORS = [
        'paper' => 0.5,
        'plasticPPHD' => 2.0,
        'plasticPS' => 3.0,
        'plasticLDPE' => 2.0,
        'aluminium' => 10.0,
        'steel' => 2.0,
        'glass' => 0.3,
        'foodWaste' => 0.2,
        'gardenWaste' => 0.15,
        'batteries' => 4.0,
        'electronics' => 6.0,
        'tetrapak' => 0.7,
        'wood' => 0.5,
    ];

    /** Landfill avoidance emission factors (kg CO₂e per kg) */
    private const LANDFILL_AVOIDANCE_EF_FACTORS = [
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
        'wood' => 1.2,
    ];

    /** Other offsets factors (divided by 25 in calculation) */
    private const OTHER_OFFSETS_FACTORS = [
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
        'wood' => 0.8,
    ];

    /**
     * Default category weights structure (all keys used in calculations).
     *
     * @return array<string, float>
     */
    public static function defaultCategoryWeights(): array
    {
        return [
            'paper' => 0,
            'plastics' => 0,
            'aluminium' => 0,
            'organics' => 0,
            'tetrapak' => 0,
            'steel' => 0,
            'glass' => 0,
            'wood' => 0,
        ];
    }

    /**
     * Build category weights from material summaries (waste stream + grade mapping).
     * Use organicsRecoveredOverride when organics come from elsewhere (e.g. grades table).
     *
     * @param  iterable<object>  $summaries  Each item must have: total_weight, material.wasteStream.name, material.grade.name
     * @param  float  $organicsRecoveredOverride  Optional override for organics weight (e.g. from grades)
     * @return array<string, float>
     */
    public function buildCategoryWeightsFromSummaries(iterable $summaries, float $organicsRecoveredOverride = 0): array
    {
        $categoryWeights = self::defaultCategoryWeights();

        if ($organicsRecoveredOverride > 0) {
            $categoryWeights['organics'] = $organicsRecoveredOverride;
        }

        foreach ($summaries as $summary) {
            if (! $summary->material || ! $summary->material->grade || ! $summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $wasteStreamName = trim($summary->material->wasteStream->name);
            $gradeName = trim($summary->material->grade->name);

            if ($wasteStreamName === 'Organic Waste' && $gradeName === 'Organics Recovered') {
                $categoryWeights['organics'] += $weight;
            } elseif ($gradeName === 'Tetrapak' || $wasteStreamName === 'Tetrapak') {
                $categoryWeights['tetrapak'] += $weight;
            } elseif ($wasteStreamName === 'Paper' && $gradeName !== 'Tetrapak') {
                $categoryWeights['paper'] += $weight;
            } elseif ($wasteStreamName === 'Plastic') {
                $categoryWeights['plastics'] += $weight;
            } elseif ($wasteStreamName === 'Aluminium') {
                $categoryWeights['aluminium'] += $weight;
            } elseif ($wasteStreamName === 'Metal' && in_array($gradeName, ['Heavy Steel', 'Light Steel', 'Light Steel Cans', 'Light Steel Drums'], true)) {
                $categoryWeights['steel'] += $weight;
            } elseif ($wasteStreamName === 'Glass') {
                $categoryWeights['glass'] += $weight;
            } elseif ($wasteStreamName === 'Wood') {
                $categoryWeights['wood'] += $weight;
            }
        }

        return $categoryWeights;
    }

    /**
     * Same category mapping as {@see buildCategoryWeightsFromSummaries()} for order waste stream lines.
     *
     * @param  iterable<object>  $streams  Each: nett_weight, material.wasteStream.name, material.grade.name
     * @return array<string, float>
     */
    public function buildCategoryWeightsFromWasteStreams(iterable $streams): array
    {
        $categoryWeights = self::defaultCategoryWeights();

        foreach ($streams as $stream) {
            if (! $stream->material || ! $stream->material->grade || ! $stream->material->wasteStream) {
                continue;
            }

            $weight = (float) $stream->nett_weight;
            $wasteStreamName = trim($stream->material->wasteStream->name);
            $gradeName = trim($stream->material->grade->name);

            if ($wasteStreamName === 'Organic Waste' && $gradeName === 'Organics Recovered') {
                $categoryWeights['organics'] += $weight;
            } elseif ($gradeName === 'Tetrapak' || $wasteStreamName === 'Tetrapak') {
                $categoryWeights['tetrapak'] += $weight;
            } elseif ($wasteStreamName === 'Paper' && $gradeName !== 'Tetrapak') {
                $categoryWeights['paper'] += $weight;
            } elseif ($wasteStreamName === 'Plastic') {
                $categoryWeights['plastics'] += $weight;
            } elseif ($wasteStreamName === 'Aluminium') {
                $categoryWeights['aluminium'] += $weight;
            } elseif ($wasteStreamName === 'Metal' && in_array($gradeName, ['Heavy Steel', 'Light Steel', 'Light Steel Cans', 'Light Steel Drums'], true)) {
                $categoryWeights['steel'] += $weight;
            } elseif ($wasteStreamName === 'Glass') {
                $categoryWeights['glass'] += $weight;
            } elseif ($wasteStreamName === 'Wood') {
                $categoryWeights['wood'] += $weight;
            }
        }

        return $categoryWeights;
    }

    /**
     * Calculate environmental impact from category weights.
     * Returns trees saved, energy saved (same unit as factors), water saved (kL), lifecycle carbon saved (kg CO₂e),
     * and SA carbon equivalency metrics from that lifecycle total.
     *
     * @param  array<string, float>  $categoryWeights  Keys: paper, plastics, aluminium, organics, tetrapak, steel, glass, wood
     * @return array{
     *     treesSaved: float,
     *     energySaved: float,
     *     waterSaved: float,
     *     co2Saved: float,
     *     electricityEquivalentKwhSaGrid: float,
     *     transportEquivalentKm: float,
     *     fuelEquivalentLitresPetrol: float,
     *     carsOffRoadAnnualEquivalent: float
     * }
     */
    public function calculateImpactFromCategoryWeights(array $categoryWeights): array
    {
        $categoryWeights = array_merge(self::defaultCategoryWeights(), $categoryWeights);

        $totalPaperWeight = $categoryWeights['paper'];
        $treesSaved = round($totalPaperWeight * self::TREES_PER_KG_PAPER, 2);

        $energySaved = 0;
        foreach ($categoryWeights as $category => $weight) {
            $energySaved += $weight * (self::ENERGY_FACTORS[$category] ?? 0);
        }
        $energySaved = round($energySaved, 2);

        $waterBreakdown = $this->waterCalculator->calculate($categoryWeights);
        $waterSaved = $waterBreakdown['totalKilolitres'];

        $co2Saved = $this->calculateLifecycleCarbonSaved($categoryWeights);
        $equivalency = $this->lifecycleCarbonEquivalency->fromLifecycleSavingKgCo2e($co2Saved);

        return [
            'treesSaved' => $treesSaved,
            'energySaved' => $energySaved,
            'waterSaved' => $waterSaved,
            'co2Saved' => $co2Saved,
            'electricityEquivalentKwhSaGrid' => $equivalency['electricityEquivalentKwhSaGrid'],
            'transportEquivalentKm' => $equivalency['transportEquivalentKm'],
            'fuelEquivalentLitresPetrol' => $equivalency['fuelEquivalentLitresPetrol'],
            'carsOffRoadAnnualEquivalent' => $equivalency['carsOffRoadAnnualEquivalent'],
        ];
    }

    /**
     * Lifecycle carbon saved (kg CO₂e): scope3EF + landfillAvoidanceEF per material.
     *
     * Spreadsheet reference column (recycling substitution factor) is not included
     * in lifecycle totals.
     *
     * @param  array<string, float>  $categoryWeights
     */
    public function calculateLifecycleCarbonSaved(array $categoryWeights): float
    {
        $weights = [
            'paper' => $categoryWeights['paper'] ?? 0,
            'plasticPPHD' => $categoryWeights['plastics'] ?? 0, // simplified: all plastics as PP/HD
            'plasticPS' => 0,
            'plasticLDPE' => 0,
            'aluminium' => $categoryWeights['aluminium'] ?? 0,
            'steel' => $categoryWeights['steel'] ?? 0,
            'glass' => $categoryWeights['glass'] ?? 0,
            'foodWaste' => $categoryWeights['organics'] ?? 0,
            'gardenWaste' => 0,
            'batteries' => 0,
            'electronics' => 0,
            'tetrapak' => $categoryWeights['tetrapak'] ?? 0,
            'wood' => $categoryWeights['wood'] ?? 0,
        ];

        $totalCO2 = 0;
        foreach ($weights as $key => $weight) {
            $scope3EF = $weight * (self::SCOPE3_EF_FACTORS[$key] ?? 0);
            $landfillAvoidanceEF = $weight * (self::LANDFILL_AVOIDANCE_EF_FACTORS[$key] ?? 0);
            $totalCO2 += $scope3EF + $landfillAvoidanceEF;
        }

        return round($totalCO2, 2);
    }
}
