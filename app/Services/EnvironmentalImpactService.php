<?php

namespace App\Services;

use Illuminate\Support\Collection;

class EnvironmentalImpactService
{
    private const CO2E_FACTORS = [
        'paper' => [
            'scope3' => 0.5,
            'landfill_avoidance' => 0.78,
            'other_offsets' => 1.3,
        ],
        'plastic_pp_hd' => [
            'scope3' => 2.0,
            'landfill_avoidance' => 0.08,
            'other_offsets' => 2.5,
        ],
        'plastic_ps' => [
            'scope3' => 3.0,
            'landfill_avoidance' => 0.05,
            'other_offsets' => 3.5,
        ],
        'plastic_ldpe' => [
            'scope3' => 2.0,
            'landfill_avoidance' => 0.06,
            'other_offsets' => 2.5,
        ],
        'aluminium' => [
            'scope3' => 10.0,
            'landfill_avoidance' => 9.0,
            'other_offsets' => 12.0,
        ],
        'steel' => [
            'scope3' => 2.0,
            'landfill_avoidance' => 2.0,
            'other_offsets' => 2.0,
        ],
        'glass' => [
            'scope3' => 0.3,
            'landfill_avoidance' => 0.03,
            'other_offsets' => 0.5,
        ],
        'tetrapak' => [
            'scope3' => 0.7,
            'landfill_avoidance' => 0.25,
            'other_offsets' => 1.0,
        ],
        'wood' => [
            'scope3' => 0.5,
            'landfill_avoidance' => 1.2,
            'other_offsets' => 0.8,
        ],
    ];

    public function __construct(
        private readonly WasteImpactCalculator $wasteImpactCalculator = new WasteImpactCalculator,
        private readonly LandfillSpaceCalculator $landfillSpaceCalculator = new LandfillSpaceCalculator,
        private readonly LifecycleCarbonEquivalency $lifecycleCarbonEquivalency = new LifecycleCarbonEquivalency,
    ) {}

    public function calculateImpact(Collection $wasteStreams): array
    {
        $totalRecyclingWeight = 0;
        $totalWasteWeight = 0;
        $materialBreakdown = [];
        $carbonBreakdown = [];

        foreach ($wasteStreams as $stream) {
            if (! $stream->material || ! $stream->material->wasteStream) {
                continue;
            }

            $weight = (float) $stream->nett_weight;
            $wasteStreamName = strtolower($stream->material->wasteStream->name ?? '');
            $gradeName = strtolower($stream->material->grade->name ?? '');

            if (str_contains($wasteStreamName, 'recycling') || str_contains($wasteStreamName, 'recycl')) {
                $totalRecyclingWeight += $weight;
            } else {
                $totalWasteWeight += $weight;
            }

            $materialType = $this->categorizeMaterial($wasteStreamName, $gradeName);

            if (! isset($materialBreakdown[$materialType])) {
                $materialBreakdown[$materialType] = 0;
            }
            $materialBreakdown[$materialType] += $weight;

            $carbonData = $this->calculateCarbonForMaterial($materialType, $weight);
            if ($carbonData) {
                if (! isset($carbonBreakdown[$materialType])) {
                    $carbonBreakdown[$materialType] = [
                        'weight' => 0,
                        'scope3' => 0,
                        'landfill_avoidance' => 0,
                        'other_offsets' => 0,
                        'lifecycle_saving' => 0,
                    ];
                }
                $carbonBreakdown[$materialType]['weight'] += $weight;
                $carbonBreakdown[$materialType]['scope3'] += $carbonData['scope3'];
                $carbonBreakdown[$materialType]['landfill_avoidance'] += $carbonData['landfill_avoidance'];
                $carbonBreakdown[$materialType]['other_offsets'] += $carbonData['other_offsets'];
                $carbonBreakdown[$materialType]['lifecycle_saving'] += $carbonData['lifecycle_saving'];
            }
        }

        $totalIncomingWaste = $totalWasteWeight + $totalRecyclingWeight;
        $divertedFromLandfill = $totalIncomingWaste > 0
            ? ($totalRecyclingWeight / $totalIncomingWaste) * 100
            : 0;

        $totalScope3 = array_sum(array_column($carbonBreakdown, 'scope3'));
        $totalLandfillAvoidance = array_sum(array_column($carbonBreakdown, 'landfill_avoidance'));
        $totalOtherOffsets = array_sum(array_column($carbonBreakdown, 'other_offsets'));
        $totalLifecycleSaving = array_sum(array_column($carbonBreakdown, 'lifecycle_saving'));

        $categoryWeights = $this->wasteImpactCalculator->buildCategoryWeightsFromWasteStreams($wasteStreams);
        $resourceImpact = $this->wasteImpactCalculator->calculateImpactFromCategoryWeights($categoryWeights);
        $landfillBreakdown = $this->landfillSpaceCalculator->calculate($categoryWeights);
        $equivalency = $this->lifecycleCarbonEquivalency->fromLifecycleSavingKgCo2e($totalLifecycleSaving);

        return [
            'trees_saved' => $resourceImpact['treesSaved'],
            'energy_saved' => $resourceImpact['energySaved'],
            'water_saved' => $resourceImpact['waterSaved'],
            'total_waste_weight' => round($totalWasteWeight, 2),
            'total_recycling_weight' => round($totalRecyclingWeight, 2),
            'total_incoming_waste' => round($totalIncomingWaste, 2),
            'diverted_from_landfill_percent' => round($divertedFromLandfill, 2),
            'landfill_space_saved' => $landfillBreakdown['total'],
            'material_breakdown' => $materialBreakdown,
            'carbon_breakdown' => $carbonBreakdown,
            'total_scope3' => round($totalScope3, 2),
            'total_landfill_avoidance' => round($totalLandfillAvoidance, 2),
            'total_other_offsets' => round($totalOtherOffsets, 2),
            'total_lifecycle_saving' => round($totalLifecycleSaving, 2),
            'km_equivalent' => round($equivalency['transportEquivalentKm'], 0),
            'electricity_equivalent_kwh_sa_grid' => $equivalency['electricityEquivalentKwhSaGrid'],
            'transport_equivalent_km' => $equivalency['transportEquivalentKm'],
            'fuel_equivalent_litres_petrol' => $equivalency['fuelEquivalentLitresPetrol'],
            'cars_off_road_annual_equivalent' => $equivalency['carsOffRoadAnnualEquivalent'],
        ];
    }

    private function categorizeMaterial(string $wasteStreamName, string $gradeName): string
    {
        $combined = strtolower($wasteStreamName.' '.$gradeName);

        if (str_contains($combined, 'paper') || str_contains($combined, 'hl') || str_contains($combined, 'tissue')) {
            return 'paper';
        }
        if (str_contains($combined, 'aluminium') || str_contains($combined, 'alu')) {
            return 'aluminium';
        }
        if (str_contains($combined, 'steel') || str_contains($combined, 'light steel')) {
            return 'steel';
        }
        if (str_contains($combined, 'glass')) {
            return 'glass';
        }
        if (str_contains($combined, 'tetrapak')) {
            return 'tetrapak';
        }
        if (str_contains($combined, 'wood') || str_contains($combined, 'timber') || str_contains($combined, 'pallet')) {
            return 'wood';
        }
        if (str_contains($combined, 'hd') || str_contains($combined, 'pp') || str_contains($combined, 'polypropylene')) {
            return 'plastic_pp_hd';
        }
        if (str_contains($combined, 'polystyrene') || str_contains($combined, 'eps') || str_contains($combined, 'xps') || str_contains($combined, 'ps')) {
            return 'plastic_ps';
        }
        if (str_contains($combined, 'ldpe') || str_contains($combined, 'ld') || str_contains($combined, 'film')) {
            return 'plastic_ldpe';
        }

        return 'other';
    }

    private function calculateCarbonForMaterial(string $materialType, float $weight): ?array
    {
        if (! isset(self::CO2E_FACTORS[$materialType])) {
            return null;
        }

        $factors = self::CO2E_FACTORS[$materialType];

        $scope3 = $weight * $factors['scope3'];
        $landfillAvoidance = $weight * $factors['landfill_avoidance'];
        $otherOffsets = $weight * $factors['other_offsets'];
        $lifecycleSaving = $scope3 + $landfillAvoidance;

        return [
            'scope3' => $scope3,
            'landfill_avoidance' => $landfillAvoidance,
            'other_offsets' => $otherOffsets,
            'lifecycle_saving' => $lifecycleSaving,
        ];
    }

    public function getMaterialDisplayName(string $materialType): string
    {
        return match ($materialType) {
            'paper' => 'Paper',
            'plastic_pp_hd' => 'Plastic PP / HD',
            'plastic_ps' => 'Plastic PS (Polystyrene)',
            'plastic_ldpe' => 'Plastic LDPE Film',
            'aluminium' => 'Aluminium',
            'steel' => 'Steel',
            'glass' => 'Glass',
            'tetrapak' => 'Tetrapak variants',
            'wood' => 'Wood (Timber / Pallets)',
            default => 'Other',
        };
    }
}
