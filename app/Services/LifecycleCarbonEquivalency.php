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
     * kg CO₂e per barrel of crude oil — equivalency: barrels = CO₂e ÷ this.
     * EPA Greenhouse Gas Equivalencies Calculator: 5.80 mmbtu/barrel × 20.31 kg C/mmbtu × 44/12 kg CO2/kg C.
     */
    private const KG_CO2E_PER_BARREL_OF_OIL = 431.9;

    /**
     * kg CO₂e per home's electricity use for one month — equivalency: homes = CO₂e ÷ this.
     * EPA Greenhouse Gas Equivalencies Calculator: 4.798 metric tons CO2/home/year ÷ 12 months.
     */
    private const KG_CO2E_PER_HOME_POWERED_ONE_MONTH = 399.83;

    /**
     * @return array{
     *     electricityEquivalentKwhSaGrid: float,
     *     transportEquivalentKm: float,
     *     fuelEquivalentLitresPetrol: float,
     *     carsOffRoadAnnualEquivalent: float,
     *     barrelsOfOilSaved: float,
     *     homesPoweredOneMonth: float
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
                'barrelsOfOilSaved' => 0.0,
                'homesPoweredOneMonth' => 0.0,
            ];
        }

        return [
            'electricityEquivalentKwhSaGrid' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_KWH_SA_GRID, 2),
            'transportEquivalentKm' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_KM_TRANSPORT, 2),
            'fuelEquivalentLitresPetrol' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_LITRE_PETROL, 2),
            'carsOffRoadAnnualEquivalent' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_CAR_YEAR, 4),
            'barrelsOfOilSaved' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_BARREL_OF_OIL, 2),
            'homesPoweredOneMonth' => round($lifecycleSavingKgCo2e / self::KG_CO2E_PER_HOME_POWERED_ONE_MONTH, 2),
        ];
    }
}
