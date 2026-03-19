<?php

use App\Services\CarbonCalculator;
use App\Services\EnvironmentalImpactService;
use App\Services\WasteImpactCalculator;

test('CarbonCalculator matches spreadsheet lifecycle formula', function () {
    $calculator = new CarbonCalculator;

    $weightsByMaterialKey = [
        'paper' => 85,
        'plasticPPHD' => 35,
        'plasticPS' => 10,
        'plasticLDPE' => 13,
        'aluminium' => 12,
        'steel' => 25,
        'glass' => 6,
        'foodWaste' => 8,
        'gardenWaste' => 2,
        'batteries' => 5,
        'electronics' => 9,
        'tetrapak' => 10,
    ];

    $result = $calculator->calculateMaterialsCO2e($weightsByMaterialKey);

    expect($result['totals']['scope3EF'])->toBe(423.2);
    expect($result['totals']['landfillAvoidanceEF'])->toBe(254.16);
    expect($result['totals']['lifecycleSaving'])->toBe(677.36);
    expect($result['materials'][0]['material'])->toBe('Paper');
    expect($result['materials'][0]['recyclingSubstitutionFactor'])->toBe(1.3);
    expect($result['materials'][11]['material'])->toBe('Tetrapak variants');
    expect($result['materials'][11]['recyclingSubstitutionFactor'])->toBe(1.0);
});

test('WasteImpactCalculator lifecycle excludes other offsets', function () {
    $calculator = new WasteImpactCalculator;

    $categoryWeights = [
        'paper' => 85,
        'plastics' => 35, // mapped to plasticPPHD in the calculator
        'aluminium' => 12,
        'steel' => 25,
        'glass' => 6,
        'organics' => 8, // mapped to foodWaste in the calculator
        'tetrapak' => 10,
    ];

    $lifecycle = $calculator->calculateLifecycleCarbonSaved($categoryWeights);

    // lifecycle = paper + plastics + aluminium + steel + glass + foodWaste + tetrapak
    // = 108.80 + 72.80 + 228.00 + 100.00 + 1.98 + 7.20 + 9.50
    expect($lifecycle)->toBe(528.28);
});

test('EnvironmentalImpactService lifecycle excludes other offsets', function () {
    $service = new EnvironmentalImpactService;

    $streams = collect([
        (object) [
            'nett_weight' => 85,
            'material' => (object) [
                'wasteStream' => (object) ['name' => 'Paper'],
                'grade' => (object) ['name' => 'General Waste'],
            ],
        ],
        (object) [
            'nett_weight' => 35,
            'material' => (object) [
                'wasteStream' => (object) ['name' => 'Plastic'],
                'grade' => (object) ['name' => 'HD'],
            ],
        ],
        (object) [
            'nett_weight' => 10,
            'material' => (object) [
                'wasteStream' => (object) ['name' => 'Plastic'],
                'grade' => (object) ['name' => 'EPS/XPS'],
            ],
        ],
        (object) [
            'nett_weight' => 13,
            'material' => (object) [
                'wasteStream' => (object) ['name' => 'Plastic'],
                'grade' => (object) ['name' => 'LDPE Film'],
            ],
        ],
        (object) [
            'nett_weight' => 12,
            'material' => (object) [
                'wasteStream' => (object) ['name' => 'Aluminium'],
                'grade' => (object) ['name' => 'Any'],
            ],
        ],
        (object) [
            'nett_weight' => 25,
            'material' => (object) [
                'wasteStream' => (object) ['name' => 'Metal'],
                'grade' => (object) ['name' => 'Light Steel'],
            ],
        ],
        (object) [
            'nett_weight' => 6,
            'material' => (object) [
                'wasteStream' => (object) ['name' => 'Glass'],
                'grade' => (object) ['name' => 'Any'],
            ],
        ],
        (object) [
            'nett_weight' => 10,
            'material' => (object) [
                'wasteStream' => (object) ['name' => 'Tetrapak'],
                'grade' => (object) ['name' => 'Any'],
            ],
        ],
    ]);

    $impact = $service->calculateImpact($streams);

    // lifecycle_saving must equal scope3 + landfill_avoidance only (no other_offsets)
    expect($impact['total_lifecycle_saving'])->toBe(578.36);
});
