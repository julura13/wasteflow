<?php

use App\Services\CarbonCalculator;
use App\Services\WasteImpactCalculator;

test('CarbonCalculator wood row matches workbook (100 kg example)', function () {
    $calculator = new CarbonCalculator;

    $result = $calculator->calculateMaterialsCO2e(['wood' => 100]);

    $woodRow = collect($result['materials'])->firstWhere('material', 'Wood – Reuse (Pallets & Timber)');
    expect($woodRow['scope3EF'])->toBe(150.0)       // 100 × 1.5
        ->and($woodRow['landfillAvoidanceEF'])->toBe(60.0)  // 100 × 0.6
        ->and($woodRow['lifecycleSaving'])->toBe(210.0)
        ->and($woodRow['recyclingSubstitutionFactor'])->toBe(0.8);
});

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
        'wood' => 0,
    ];

    $result = $calculator->calculateMaterialsCO2e($weightsByMaterialKey);

    expect($result['totals']['scope3EF'])->toBe(423.2);
    expect($result['totals']['landfillAvoidanceEF'])->toBe(96.16);  // aluminium (0) + steel (0) now zero
    expect($result['totals']['lifecycleSaving'])->toBe(519.36);
    expect($result['materials'][0]['material'])->toBe('Paper');
    expect($result['materials'][0]['recyclingSubstitutionFactor'])->toBe(1.3);
    expect($result['materials'][11]['material'])->toBe('Tetrapak variants');
    expect($result['materials'][11]['recyclingSubstitutionFactor'])->toBe(1.0);
    expect($result['materials'][12]['material'])->toBe('Wood – Reuse (Pallets & Timber)');
    expect($result['materials'][12]['recyclingSubstitutionFactor'])->toBe(0.8);
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
        'wood' => 0,
    ];

    $lifecycle = $calculator->calculateLifecycleCarbonSaved($categoryWeights);

    // lifecycle = paper + plastics + aluminium + steel + glass + foodWaste + tetrapak
    // = 108.80 + 72.80 + 120.00 + 50.00 + 1.98 + 7.20 + 9.50 (aluminium/steel landfill now 0)
    expect($lifecycle)->toBe(370.28);
});

test('buildCarbonWeightsFromWasteStreams lifecycle with grade-split plastics', function () {
    $calculator = new WasteImpactCalculator;

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

    $carbonWeights = $calculator->buildCarbonWeightsFromWasteStreams($streams);
    $lifecycle = $calculator->calculateLifecycleCarbonSaved($carbonWeights);

    // Grade-split plastics: PPHD=35, PS=10, LDPE=13 vs old simple 'plastics=58 as PPHD'
    // paper(85×1.28) + PPHD(35×2.08) + PS(10×3.05) + LDPE(13×2.06) + Al(12×10) + steel(25×2) + glass(6×0.33) + tetrapak(10×0.95)
    expect($lifecycle)->toBe(420.36);
});

test('WasteImpactCalculator derives barrels of oil and homes powered from lifecycle CO2e (EPA factors)', function () {
    $calculator = new WasteImpactCalculator;

    // 100 kg paper × 10 kWh/kg energy factor = 1000 kWh energy saved (unaffected by this formula)
    // 100 kg paper × (0.5 scope3 + 0.78 landfill avoidance) kg CO2e/kg = 128 kg CO2e lifecycle saving
    $categoryWeights = ['paper' => 100];

    $impact = $calculator->calculateImpactFromCategoryWeights($categoryWeights);

    expect($impact['energySaved'])->toBe(1000.0)
        ->and($impact['co2Saved'])->toBe(128.0)
        ->and($impact['barrelsOfOilSaved'])->toBe(0.3)     // 128 ÷ 431.9 (EPA kg CO2e/barrel)
        ->and($impact['homesPoweredOneMonth'])->toBe(0.32); // 128 ÷ 399.83 (EPA kg CO2e/home/month)
});

test('WasteImpactCalculator carbon-weights path also derives barrels of oil and homes powered from lifecycle CO2e', function () {
    $calculator = new WasteImpactCalculator;

    // 100 kg paper (carbon-key path) → same 128 kg CO2e lifecycle saving as the category-weights path
    $carbonWeights = array_merge(WasteImpactCalculator::defaultCarbonWeights(), ['paper' => 100]);

    $impact = $calculator->calculateImpactFromCarbonWeights($carbonWeights);

    expect($impact['energySaved'])->toBe(1000.0)
        ->and($impact['co2Saved'])->toBe(128.0)
        ->and($impact['barrelsOfOilSaved'])->toBe(0.3)
        ->and($impact['homesPoweredOneMonth'])->toBe(0.32);
});
