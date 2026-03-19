<?php

namespace App\Services;

class CarbonCalculator
{
    /**
     * Spreadsheet (docs/Carbon Calculator.xlsx) upstream avoided emission factor
     * (column C): kg CO2e per kg.
     *
     * Keys match ReportController material weights.
     *
     * @var array<string, float>
     */
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
    ];

    /**
     * Spreadsheet (docs/Carbon Calculator.xlsx) landfill avoidance emission factor
     * (column E): kg CO2e per kg.
     *
     * @var array<string, float>
     */
    private const LANDFILL_AVOIDANCE_EF_FACTORS = [
        'paper' => 0.78,
        'plasticPPHD' => 0.08,
        'plasticPS' => 0.05,
        'plasticLDPE' => 0.06,
        'aluminium' => 9.0,
        'steel' => 2.0,
        'glass' => 0.03,
        'foodWaste' => 0.7,
        'gardenWaste' => 0.5,
        'batteries' => 1.5,
        'electronics' => 1.0,
        'tetrapak' => 0.25,
    ];

    /**
     * Spreadsheet (docs/Carbon Calculator.xlsx) recycling substitution factor
     * (column G). Reference only, not included in lifecycle total.
     *
     * @var array<string, float>
     */
    private const SUBSTITUTION_FACTOR_EF_FACTORS = [
        'paper' => 1.3,
        'plasticPPHD' => 2.5,
        'plasticPS' => 3.5,
        'plasticLDPE' => 2.5,
        'aluminium' => 12.0,
        'steel' => 2.0,
        'glass' => 0.5,
        'foodWaste' => 0.3,
        'gardenWaste' => 0.25,
        'batteries' => 5.0,
        'electronics' => 8.0,
        'tetrapak' => 1.0,
    ];

    /**
     * Calculate per-material carbon values.
     *
     * Lifecycle total must match spreadsheet column H:
     * - D (Weight * Scope3 factor)
     * - F (Weight * Landfill factor)
     * - H = D + F
     * Column G is reference only and must NOT be included.
     *
     * @param  array<string, float>  $weightsByMaterialKey
     * @return array{
     *     materials: array<int, array{material: string, weight: int, scope3EF: float, landfillAvoidanceEF: float, otherOffsets: float, lifecycleSaving: float}>,
     *     totals: array{scope3EF: float, landfillAvoidanceEF: float, otherOffsets: float, lifecycleSaving: float}
     * }
     */
    public function calculateMaterialsCO2e(array $weightsByMaterialKey): array
    {
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
            'Tetrapak variants' => 'tetrapak',
        ];

        $materials = [];
        $totals = [
            'scope3EF' => 0.0,
            'landfillAvoidanceEF' => 0.0,
            'otherOffsets' => 0.0,
            'lifecycleSaving' => 0.0,
        ];

        foreach ($materialOrder as $displayName => $key) {
            $rawWeight = (float) ($weightsByMaterialKey[$key] ?? 0.0);
            $displayWeight = (int) round($rawWeight, 0);

            $scope3Factor = self::SCOPE3_EF_FACTORS[$key] ?? 0.0;
            $landfillFactor = self::LANDFILL_AVOIDANCE_EF_FACTORS[$key] ?? 0.0;
            $substitutionFactor = self::SUBSTITUTION_FACTOR_EF_FACTORS[$key] ?? 0.0;

            $scope3EF = round($rawWeight * $scope3Factor, 2);
            $landfillAvoidanceEF = round($rawWeight * $landfillFactor, 2);
            $otherOffsets = round($rawWeight * $substitutionFactor, 2);

            $lifecycleSaving = round($scope3EF + $landfillAvoidanceEF, 2);

            $totals['scope3EF'] += $scope3EF;
            $totals['landfillAvoidanceEF'] += $landfillAvoidanceEF;
            $totals['otherOffsets'] += $otherOffsets;
            $totals['lifecycleSaving'] += $lifecycleSaving;

            $materials[] = [
                'material' => $displayName,
                'weight' => $displayWeight,
                'scope3EF' => $scope3EF,
                'landfillAvoidanceEF' => $landfillAvoidanceEF,
                'otherOffsets' => $otherOffsets,
                'lifecycleSaving' => $lifecycleSaving,
            ];
        }

        foreach (array_keys($totals) as $k) {
            $totals[$k] = round($totals[$k], 2);
        }

        return [
            'materials' => $materials,
            'totals' => $totals,
        ];
    }
}
