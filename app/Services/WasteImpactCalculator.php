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

    /** Energy saved factors (per kg) by simple category key */
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
        'wood' => 1.5,
    ];

    /** Landfill avoidance emission factors (kg CO₂e per kg) */
    private const LANDFILL_AVOIDANCE_EF_FACTORS = [
        'paper' => 0.78,
        'plasticPPHD' => 0.08,
        'plasticPS' => 0.05,
        'plasticLDPE' => 0.06,
        'aluminium' => 0,
        'steel' => 0,
        'glass' => 0.03,
        'foodWaste' => 0.7,
        'gardenWaste' => 0.5,
        'batteries' => 1.5,
        'electronics' => 1,
        'tetrapak' => 0.25,
        'wood' => 0.6,
    ];

    /**
     * Default carbon weight keys (used by CarbonCalculator and lifecycle carbon calculations).
     *
     * @return array<string, float>
     */
    public static function defaultCarbonWeights(): array
    {
        return [
            'paper' => 0.0,
            'plasticPPHD' => 0.0,
            'plasticPS' => 0.0,
            'plasticLDPE' => 0.0,
            'aluminium' => 0.0,
            'steel' => 0.0,
            'glass' => 0.0,
            'foodWaste' => 0.0,
            'gardenWaste' => 0.0,
            'batteries' => 0.0,
            'electronics' => 0.0,
            'tetrapak' => 0.0,
            'wood' => 0.0,
        ];
    }

    /**
     * Default simple category keys (used by energy, trees, water, landfill-space calculations).
     *
     * @return array<string, float>
     */
    public static function defaultCategoryWeights(): array
    {
        return [
            'paper' => 0.0,
            'plastics' => 0.0,
            'aluminium' => 0.0,
            'organics' => 0.0,
            'tetrapak' => 0.0,
            'steel' => 0.0,
            'glass' => 0.0,
            'wood' => 0.0,
        ];
    }

    /**
     * Canonical material categorisation from aggregated summaries.
     *
     * Returns the detailed carbon weight map consumed by {@see CarbonCalculator::calculateMaterialsCO2e()}
     * and {@see calculateLifecycleCarbonSaved()}. This is the single place that maps
     * (wasteStream, grade) → material key; all other categorisation helpers delegate here.
     *
     * Fixes applied versus the old per-method loops:
     * - Plastics are split by grade (PP/HD, PS, LDPE) with an explicit fallback to PP/HD
     *   so no plastic weight is silently dropped.
     * - When $organicsOverride > 0, organic waste rows in the summaries are skipped to
     *   prevent double-counting.
     * - Garden waste, batteries, and electronics are now captured.
     *
     * @param  iterable<object>  $summaries  Each must have: total_weight, material.wasteStream.name, material.grade.name
     * @param  float  $organicsOverride  When > 0, foodWaste is set to this value; Organic Waste rows are skipped in the loop.
     * @return array{paper: float, plasticPPHD: float, plasticPS: float, plasticLDPE: float, aluminium: float, steel: float, glass: float, foodWaste: float, gardenWaste: float, batteries: float, electronics: float, tetrapak: float, wood: float}
     */
    public function buildCarbonWeightsFromSummaries(iterable $summaries, float $organicsOverride = 0): array
    {
        $weights = self::defaultCarbonWeights();

        if ($organicsOverride > 0) {
            $weights['foodWaste'] = $organicsOverride;
        }

        foreach ($summaries as $summary) {
            if (! $summary->material || ! $summary->material->grade || ! $summary->material->wasteStream) {
                continue;
            }

            $weight = (float) $summary->total_weight;
            $ws = trim($summary->material->wasteStream->name);
            $grade = trim($summary->material->grade->name);

            if ($grade === 'Tetrapak' || $ws === 'Tetrapak') {
                $weights['tetrapak'] += $weight;
            } elseif ($ws === 'Paper') {
                $weights['paper'] += $weight;
            } elseif ($ws === 'Plastic') {
                if (str_starts_with($grade, 'HD') || in_array($grade, ['PP', 'PP Caps'], true)) {
                    $weights['plasticPPHD'] += $weight;
                } elseif (in_array($grade, ['EPS/XPS', 'XPS'], true)) {
                    $weights['plasticPS'] += $weight;
                } elseif (str_starts_with($grade, 'LD')) {
                    $weights['plasticLDPE'] += $weight;
                } else {
                    $weights['plasticPPHD'] += $weight; // fallback: unrecognised grade → PP/HD
                }
            } elseif ($ws === 'Aluminium') {
                $weights['aluminium'] += $weight;
            } elseif ($ws === 'Metal' && in_array($grade, ['Heavy Steel', 'Light Steel', 'Light Steel Cans', 'Light Steel Drums'], true)) {
                $weights['steel'] += $weight;
            } elseif ($ws === 'Glass') {
                $weights['glass'] += $weight;
            } elseif ($ws === 'Organic Waste' && $grade === 'Organics Recovered') {
                if ($organicsOverride <= 0) {
                    $weights['foodWaste'] += $weight;
                }
                // When override is set, this row is already counted — skip to avoid double-count.
            } elseif ($ws === 'Garden Waste') {
                $weights['gardenWaste'] += $weight;
            } elseif ($ws === 'Batteries') {
                $weights['batteries'] += $weight;
            } elseif (in_array($ws, ['Electronics', 'E-waste', 'Electronics (E-waste)'], true)) {
                $weights['electronics'] += $weight;
            } elseif ($ws === 'Wood') {
                $weights['wood'] += $weight;
            }
        }

        return $weights;
    }

    /**
     * Derive simple category weights from carbon weights.
     * Used by energy, trees, water, and landfill-space calculations which do not need plastic grade detail.
     *
     * @param  array<string, float>  $carbon  From {@see buildCarbonWeightsFromSummaries()}
     * @return array<string, float>
     */
    public static function toSimpleWeights(array $carbon): array
    {
        return [
            'paper' => $carbon['paper'] ?? 0.0,
            'plastics' => ($carbon['plasticPPHD'] ?? 0.0) + ($carbon['plasticPS'] ?? 0.0) + ($carbon['plasticLDPE'] ?? 0.0),
            'aluminium' => $carbon['aluminium'] ?? 0.0,
            'organics' => $carbon['foodWaste'] ?? 0.0,
            'tetrapak' => $carbon['tetrapak'] ?? 0.0,
            'steel' => $carbon['steel'] ?? 0.0,
            'glass' => $carbon['glass'] ?? 0.0,
            'wood' => $carbon['wood'] ?? 0.0,
        ];
    }

    /**
     * Build simplified category weights from summaries.
     * Delegates to {@see buildCarbonWeightsFromSummaries()} for consistent categorisation.
     *
     * @param  iterable<object>  $summaries
     * @return array<string, float>
     */
    public function buildCategoryWeightsFromSummaries(iterable $summaries, float $organicsOverride = 0): array
    {
        return self::toSimpleWeights($this->buildCarbonWeightsFromSummaries($summaries, $organicsOverride));
    }

    /**
     * Calculate environmental impact from detailed carbon weights.
     * Energy, trees, and water use simplified keys; lifecycle CO₂e uses the full carbon split.
     *
     * Prefer this over {@see calculateImpactFromCategoryWeights()} when you have carbon weights
     * already built, as it avoids the simplified plastics-as-PP/HD approximation.
     *
     * @param  array<string, float>  $carbonWeights  From {@see buildCarbonWeightsFromSummaries()}
     */
    public function calculateImpactFromCarbonWeights(array $carbonWeights): array
    {
        $simple = self::toSimpleWeights($carbonWeights);

        $treesSaved = round($simple['paper'] * self::TREES_PER_KG_PAPER, 2);

        $energySaved = 0.0;
        foreach ($simple as $category => $weight) {
            $energySaved += $weight * (self::ENERGY_FACTORS[$category] ?? 0);
        }
        $energySaved = round($energySaved, 2);

        $waterBreakdown = $this->waterCalculator->calculate($simple);
        $waterSaved = $waterBreakdown['totalKilolitres'];

        $co2Saved = $this->calculateLifecycleCarbonSaved($carbonWeights);
        $equivalency = $this->lifecycleCarbonEquivalency->fromLifecycleSavingKgCo2e($co2Saved);

        return [
            'treesSaved' => $treesSaved,
            'energySaved' => $energySaved,
            'barrelsOfOilSaved' => $equivalency['barrelsOfOilSaved'],
            'homesPoweredOneMonth' => $equivalency['homesPoweredOneMonth'],
            'waterSaved' => $waterSaved,
            'co2Saved' => $co2Saved,
            'electricityEquivalentKwhSaGrid' => $equivalency['electricityEquivalentKwhSaGrid'],
            'transportEquivalentKm' => $equivalency['transportEquivalentKm'],
            'fuelEquivalentLitresPetrol' => $equivalency['fuelEquivalentLitresPetrol'],
            'carsOffRoadAnnualEquivalent' => $equivalency['carsOffRoadAnnualEquivalent'],
        ];
    }

    /**
     * Calculate environmental impact from simplified category weights.
     *
     * @param  array<string, float>  $categoryWeights  Keys: paper, plastics, aluminium, organics, tetrapak, steel, glass, wood
     */
    public function calculateImpactFromCategoryWeights(array $categoryWeights): array
    {
        $categoryWeights = array_merge(self::defaultCategoryWeights(), $categoryWeights);

        $treesSaved = round($categoryWeights['paper'] * self::TREES_PER_KG_PAPER, 2);

        $energySaved = 0.0;
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
            'barrelsOfOilSaved' => $equivalency['barrelsOfOilSaved'],
            'homesPoweredOneMonth' => $equivalency['homesPoweredOneMonth'],
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
     * Accepts both carbon keys (plasticPPHD/plasticPS/plasticLDPE, foodWaste, gardenWaste…)
     * and the legacy simple keys (plastics, organics) for backward compatibility.
     *
     * @param  array<string, float>  $weights
     */
    public function calculateLifecycleCarbonSaved(array $weights): float
    {
        $mapped = [
            'paper' => $weights['paper'] ?? 0,
            // Accept carbon keys; fall back to simple 'plastics' key (treated as PP/HD)
            'plasticPPHD' => ($weights['plasticPPHD'] ?? 0) + ($weights['plastics'] ?? 0),
            'plasticPS' => $weights['plasticPS'] ?? 0,
            'plasticLDPE' => $weights['plasticLDPE'] ?? 0,
            'aluminium' => $weights['aluminium'] ?? 0,
            'steel' => $weights['steel'] ?? 0,
            'glass' => $weights['glass'] ?? 0,
            // Accept carbon keys; fall back to simple 'organics' key
            'foodWaste' => ($weights['foodWaste'] ?? 0) + ($weights['organics'] ?? 0),
            'gardenWaste' => $weights['gardenWaste'] ?? 0,
            'batteries' => $weights['batteries'] ?? 0,
            'electronics' => $weights['electronics'] ?? 0,
            'tetrapak' => $weights['tetrapak'] ?? 0,
            'wood' => $weights['wood'] ?? 0,
        ];

        $totalCO2 = 0.0;
        foreach ($mapped as $key => $weight) {
            $totalCO2 += $weight * (self::SCOPE3_EF_FACTORS[$key] ?? 0);
            $totalCO2 += $weight * (self::LANDFILL_AVOIDANCE_EF_FACTORS[$key] ?? 0);
        }

        return round($totalCO2, 2);
    }

    /**
     * Same grade-split categorisation as {@see buildCarbonWeightsFromSummaries()} but for individual order
     * waste stream lines (uses nett_weight instead of total_weight).
     *
     * @param  iterable<object>  $streams  Each: nett_weight, material.wasteStream.name, material.grade.name
     * @return array<string, float> Carbon key set
     */
    public function buildCarbonWeightsFromWasteStreams(iterable $streams, float $organicsOverride = 0): array
    {
        $weights = self::defaultCarbonWeights();

        if ($organicsOverride > 0) {
            $weights['foodWaste'] = $organicsOverride;
        }

        foreach ($streams as $stream) {
            if (! $stream->material || ! $stream->material->grade || ! $stream->material->wasteStream) {
                continue;
            }

            $weight = (float) $stream->nett_weight;
            $ws = trim($stream->material->wasteStream->name);
            $grade = trim($stream->material->grade->name);

            if ($grade === 'Tetrapak' || $ws === 'Tetrapak') {
                $weights['tetrapak'] += $weight;
            } elseif ($ws === 'Paper') {
                $weights['paper'] += $weight;
            } elseif ($ws === 'Plastic') {
                if (str_starts_with($grade, 'HD') || in_array($grade, ['PP', 'PP Caps'], true)) {
                    $weights['plasticPPHD'] += $weight;
                } elseif (in_array($grade, ['EPS/XPS', 'XPS'], true)) {
                    $weights['plasticPS'] += $weight;
                } elseif (str_starts_with($grade, 'LD')) {
                    $weights['plasticLDPE'] += $weight;
                } else {
                    $weights['plasticPPHD'] += $weight;
                }
            } elseif ($ws === 'Aluminium') {
                $weights['aluminium'] += $weight;
            } elseif ($ws === 'Metal' && in_array($grade, ['Heavy Steel', 'Light Steel', 'Light Steel Cans', 'Light Steel Drums'], true)) {
                $weights['steel'] += $weight;
            } elseif ($ws === 'Glass') {
                $weights['glass'] += $weight;
            } elseif ($ws === 'Organic Waste' && $grade === 'Organics Recovered') {
                if ($organicsOverride <= 0) {
                    $weights['foodWaste'] += $weight;
                }
            } elseif ($ws === 'Garden Waste') {
                $weights['gardenWaste'] += $weight;
            } elseif ($ws === 'Batteries') {
                $weights['batteries'] += $weight;
            } elseif (in_array($ws, ['Electronics', 'E-waste', 'Electronics (E-waste)'], true)) {
                $weights['electronics'] += $weight;
            } elseif ($ws === 'Wood') {
                $weights['wood'] += $weight;
            }
        }

        return $weights;
    }
}
