<?php

namespace App\Services;

use App\Models\OrderWasteStream;
use Illuminate\Support\Collection;

class EnvironmentalImpactService
{
    private const TREES_PER_TONNE_PAPER = 17;
    private const ENERGY_PER_KG_PAPER = 4.4;
    private const WATER_PER_KG_PAPER = 10;
    
    private const CO2E_FACTORS = [
        'paper' => [
            'scope3' => 0.092,
            'landfill_avoidance' => 0.78,
            'other_offsets' => 0.6,
        ],
        'plastic_pp_hd' => [
            'scope3' => 0.18,
            'landfill_avoidance' => 0.08,
            'other_offsets' => 0.8,
        ],
        'plastic_ps' => [
            'scope3' => 0.2,
            'landfill_avoidance' => 0.05,
            'other_offsets' => 0.88,
        ],
        'plastic_ldpe' => [
            'scope3' => 0.18,
            'landfill_avoidance' => 0.06,
            'other_offsets' => 1.0,
        ],
        'aluminium' => [
            'scope3' => 0.5,
            'landfill_avoidance' => 9.0,
            'other_offsets' => 8.0,
        ],
        'steel' => [
            'scope3' => 0.25,
            'landfill_avoidance' => 2.0,
            'other_offsets' => 1.8,
        ],
        'glass' => [
            'scope3' => 0.09,
            'landfill_avoidance' => 0.03,
            'other_offsets' => 0.2,
        ],
        'tetrapak' => [
            'scope3' => 0.1,
            'landfill_avoidance' => 0.25,
            'other_offsets' => 0.2,
        ],
    ];

    public function calculateImpact(Collection $wasteStreams): array
    {
        $paperWeight = 0;
        $totalRecyclingWeight = 0;
        $totalWasteWeight = 0;
        $materialBreakdown = [];
        $carbonBreakdown = [];

        foreach ($wasteStreams as $stream) {
            if (!$stream->material || !$stream->material->wasteStream) {
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
            
            if (!isset($materialBreakdown[$materialType])) {
                $materialBreakdown[$materialType] = 0;
            }
            $materialBreakdown[$materialType] += $weight;

            if (str_contains($gradeName, 'paper') || str_contains($wasteStreamName, 'paper')) {
                $paperWeight += $weight;
            }

            $carbonData = $this->calculateCarbonForMaterial($materialType, $weight);
            if ($carbonData) {
                if (!isset($carbonBreakdown[$materialType])) {
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

        $treesSaved = ($paperWeight / 1000) * self::TREES_PER_TONNE_PAPER;
        $energySaved = $paperWeight * self::ENERGY_PER_KG_PAPER;
        $waterSaved = $paperWeight * self::WATER_PER_KG_PAPER;

        $totalIncomingWaste = $totalWasteWeight + $totalRecyclingWeight;
        $divertedFromLandfill = $totalIncomingWaste > 0 
            ? ($totalRecyclingWeight / $totalIncomingWaste) * 100 
            : 0;

        $landfillSpaceSaved = $totalRecyclingWeight * 0.003;

        $totalScope3 = array_sum(array_column($carbonBreakdown, 'scope3'));
        $totalLandfillAvoidance = array_sum(array_column($carbonBreakdown, 'landfill_avoidance'));
        $totalOtherOffsets = array_sum(array_column($carbonBreakdown, 'other_offsets'));
        $totalLifecycleSaving = array_sum(array_column($carbonBreakdown, 'lifecycle_saving'));

        $kmEquivalent = $totalLifecycleSaving * 0.4;

        return [
            'trees_saved' => round($treesSaved, 0),
            'energy_saved' => round($energySaved, 0),
            'water_saved' => round($waterSaved, 2),
            'total_waste_weight' => round($totalWasteWeight, 2),
            'total_recycling_weight' => round($totalRecyclingWeight, 2),
            'total_incoming_waste' => round($totalIncomingWaste, 2),
            'diverted_from_landfill_percent' => round($divertedFromLandfill, 2),
            'landfill_space_saved' => round($landfillSpaceSaved, 2),
            'material_breakdown' => $materialBreakdown,
            'carbon_breakdown' => $carbonBreakdown,
            'total_scope3' => round($totalScope3, 2),
            'total_landfill_avoidance' => round($totalLandfillAvoidance, 2),
            'total_other_offsets' => round($totalOtherOffsets, 2),
            'total_lifecycle_saving' => round($totalLifecycleSaving, 2),
            'km_equivalent' => round($kmEquivalent, 0),
        ];
    }

    private function categorizeMaterial(string $wasteStreamName, string $gradeName): string
    {
        $combined = strtolower($wasteStreamName . ' ' . $gradeName);

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
        if (!isset(self::CO2E_FACTORS[$materialType])) {
            return null;
        }

        $factors = self::CO2E_FACTORS[$materialType];
        
        $scope3 = $weight * $factors['scope3'];
        $landfillAvoidance = $weight * $factors['landfill_avoidance'];
        $otherOffsets = $weight * $factors['other_offsets'];
        $lifecycleSaving = $scope3 + $landfillAvoidance + $otherOffsets;

        return [
            'scope3' => $scope3,
            'landfill_avoidance' => $landfillAvoidance,
            'other_offsets' => $otherOffsets,
            'lifecycle_saving' => $lifecycleSaving,
        ];
    }

    public function getMaterialDisplayName(string $materialType): string
    {
        return match($materialType) {
            'paper' => 'Paper',
            'plastic_pp_hd' => 'Plastic PP / HD',
            'plastic_ps' => 'Plastic PS (Polystyrene)',
            'plastic_ldpe' => 'Plastic LDPE Film',
            'aluminium' => 'Aluminium',
            'steel' => 'Steel',
            'glass' => 'Glass',
            'tetrapak' => 'Tetrapak variants',
            default => 'Other',
        };
    }
}

