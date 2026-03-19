<?php

namespace App\Services;

/**
 * Carbon equivalency indicators from lifecycle saving (kg CO₂e).
 *
 * Source: docs/Dashboard & Reports - Metrics (1).docx
 */
class LifecycleCarbonEquivalency
{
    /** kg CO₂e per kWh (SA grid) — equivalency: kWh = CO₂e ÷ this */
    private const KG_CO2E_PER_KWH_SA_GRID = 0.9;

    /** kg CO₂e per km transport — equivalency: km = CO₂e ÷ this */
    private const KG_CO2E_PER_KM_TRANSPORT = 0.192;

    /** kg CO₂e per litre petrol — equivalency: L = CO₂e ÷ this */
    private const KG_CO2E_PER_LITRE_PETROL = 2.31;

    /** kg CO₂e per car-year (annual equivalent) — equivalency: cars = CO₂e ÷ this */
    private const KG_CO2E_PER_CAR_YEAR = 4600.0;

    /**
     * @return array{
     *     electricityEquivalentKwhSaGrid: float,
     *     transportEquivalentKm: float,
     *     fuelEquivalentLitresPetrol: float,
     *     carsOffRoadAnnualEquivalent: float
     * }
     */
    public function fromLifecycleSavingKgCo2e(float $lifecycleSavingKgCo2e): array
    {
        if ($lifecycleSavingKgCo2e <= 0) {
            return [
                'electricityEquivalentKwhSaGrid' => 0.0,
                'transportEquivalentKm' => 0.0,
                'fuelEquivalentLitresPetrol' => 0.0,
                'carsOffRoadAnnualEquivalent' => 0.0,
            ];
        }

        return [
            'electricityEquivalentKwhSaGrid' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_KWH_SA_GRID, 2),
            'transportEquivalentKm' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_KM_TRANSPORT, 2),
            'fuelEquivalentLitresPetrol' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_LITRE_PETROL, 2),
            'carsOffRoadAnnualEquivalent' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_CAR_YEAR, 4),
        ];
    }
}
