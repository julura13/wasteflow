<?php

namespace App\Services;

class LandfillSpaceCalculator
{
    /**
     * Landfill airspace avoided (m³) = weight (kg) ÷ density (kg/m³).
     * Density factors from docs/Landfill space saved m3.xlsx (column D).
     *
     * @var array<string, float>
     */
    private const DENSITY_KG_PER_M3 = [
        'paper' => 100.0,
        'plastics' => 65.0,
        'aluminium' => 1300.0,
        'steel' => 300.0,
        'glass' => 400.0,
        'tetrapak' => 150.0,
        'organics' => 500.0,
    ];

    /**
     * @param  array<string, float|int|string|null>  $weightsKg
     * @return array<string, array{total: float, densityKgPerM3: float, spaceSaved: float}|float>
     */
    public function calculate(array $weightsKg): array
    {
        $breakdown = [];
        $totalSpaceSaved = 0.0;

        foreach (self::DENSITY_KG_PER_M3 as $key => $density) {
            $weight = max(0.0, (float) ($weightsKg[$key] ?? 0.0));
            $spaceSaved = $density > 0 ? $weight / $density : 0.0;
            $roundedSpace = round($spaceSaved, 2);

            $breakdown[$key] = [
                'total' => round($weight, 2),
                'densityKgPerM3' => $density,
                'spaceSaved' => $roundedSpace,
            ];
            $totalSpaceSaved += $roundedSpace;
        }

        $breakdown['total'] = round($totalSpaceSaved, 2);

        return $breakdown;
    }
}
