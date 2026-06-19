<?php

namespace App\Services;

class WaterCalculator
{
    /**
     * Water saving factors from docs/Water Calculator.xlsx (column C, L per kg).
     *
     * @var array<string, float>
     */
    private const WATER_SAVING_L_PER_KG = [
        'paper' => 1800.0,
        'plastics' => 80.0,
        'aluminium' => 1300.0,
        'steel' => 50.0,
        'glass' => 25.0,
        'tetrapak' => 400.0,
        'organics' => 2.0,
        'wood' => 30.0,
    ];

    /**
     * @param  array<string, float|int|string|null>  $weightsKg  Keys: paper, plastics, aluminium, steel, glass, tetrapak, organics
     * @return array<string, array{total: float, factorLPerKg: float, waterSavedLitres: float}|float>
     */
    public function calculate(array $weightsKg): array
    {
        $breakdown = [];
        $totalLitres = 0.0;

        foreach (self::WATER_SAVING_L_PER_KG as $key => $factor) {
            $weight = max(0.0, (float) ($weightsKg[$key] ?? 0.0));
            $litres = $weight * $factor;
            $totalLitres += $litres;

            $breakdown[$key] = [
                'total' => round($weight, 2),
                'factorLPerKg' => $factor,
                'waterSavedLitres' => round($litres, 2),
            ];
        }

        $breakdown['totalLitres'] = round($totalLitres, 2);
        $breakdown['totalKilolitres'] = round($totalLitres / 1000, 2);

        return $breakdown;
    }
}
