<?php

namespace App\Services;

class CarbonCalculator
{
    /**
     * Spreadsheet (docs/Carbon Calculator.xlsx) column C — upstream (Scope 3) avoided emission factor
     * (kg CO₂e per kg).
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
     * Spreadsheet (docs/Carbon Calculator.xlsx) column E — landfill avoidance factor (kg CO₂e per kg).
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
     * Spreadsheet (docs/Carbon Calculator.xlsx) column G — fixed recycling substitution factors
     * (kg CO₂e per kg), reference only; not multiplied into column H.
     *
     * @var array<string, float>
     */
    private const RECYCLING_SUBSTITUTION_FACTORS = [
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
     * Per spreadsheet: column D = B×C, column F = B×E, column H = D+F.
     * Column G is a fixed reference factor per material (not summed in row 14 of the workbook).
     *
     * @param  array<string, float>  $weightsByMaterialKey
     * @return array{
     *     materials: array<int, array{material: string, weight: int, scope3EF: float, landfillAvoidanceEF: float, recyclingSubstitutionFactor: float, lifecycleSaving: float}>,
     *     totals: array{scope3EF: float, landfillAvoidanceEF: float, lifecycleSaving: float}
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
            'lifecycleSaving' => 0.0,
        ];

        foreach ($materialOrder as $displayName => $key) {
            $rawWeight = (float) ($weightsByMaterialKey[$key] ?? 0.0);
            $displayWeight = (int) round($rawWeight, 0);

            $scope3Factor = self::SCOPE3_EF_FACTORS[$key] ?? 0.0;
            $landfillFactor = self::LANDFILL_AVOIDANCE_EF_FACTORS[$key] ?? 0.0;
            $substitutionFactor = self::RECYCLING_SUBSTITUTION_FACTORS[$key] ?? 0.0;

            $scope3EF = round($rawWeight * $scope3Factor, 2);
            $landfillAvoidanceEF = round($rawWeight * $landfillFactor, 2);

            $lifecycleSaving = round($scope3EF + $landfillAvoidanceEF, 2);

            $totals['scope3EF'] += $scope3EF;
            $totals['landfillAvoidanceEF'] += $landfillAvoidanceEF;
            $totals['lifecycleSaving'] += $lifecycleSaving;

            $materials[] = [
                'material' => $displayName,
                'weight' => $displayWeight,
                'scope3EF' => $scope3EF,
                'landfillAvoidanceEF' => $landfillAvoidanceEF,
                'recyclingSubstitutionFactor' => round($substitutionFactor, 2),
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
