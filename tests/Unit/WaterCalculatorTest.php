<?php

use App\Services\WasteImpactCalculator;
use App\Services\WaterCalculator;

test('matches docs/Water Calculator.xlsx sample rows', function () {
    $calculator = new WaterCalculator;

    $weights = [
        'paper' => 100,
        'plastics' => 300,
        'aluminium' => 50,
        'steel' => 45,
        'glass' => 900,
        'tetrapak' => 55,
        'organics' => 45,
    ];

    $result = $calculator->calculate($weights);

    expect($result['paper']['waterSavedLitres'])->toBe(180000.0);
    expect($result['plastics']['waterSavedLitres'])->toBe(24000.0);
    expect($result['aluminium']['waterSavedLitres'])->toBe(65000.0);
    expect($result['steel']['waterSavedLitres'])->toBe(2250.0);
    expect($result['glass']['waterSavedLitres'])->toBe(22500.0);
    expect($result['tetrapak']['waterSavedLitres'])->toBe(22000.0);
    expect($result['organics']['waterSavedLitres'])->toBe(2025.0);
    expect($result['totalLitres'])->toBe(317775.0);
    expect($result['totalKilolitres'])->toBe(317.78);
});

test('exposes spreadsheet factors', function () {
    $calculator = new WaterCalculator;
    $result = $calculator->calculate(['paper' => 1]);

    expect($result['paper']['factorLPerKg'])->toBe(1800.0);
    expect($result['glass']['factorLPerKg'])->toBe(25.0);
});

test('WasteImpactCalculator delegates water saved to WaterCalculator', function () {
    $calculator = new WasteImpactCalculator;

    $impact = $calculator->calculateImpactFromCategoryWeights([
        'paper' => 100,
        'plastics' => 0,
        'aluminium' => 0,
        'steel' => 0,
        'glass' => 0,
        'tetrapak' => 0,
        'organics' => 0,
    ]);

    expect($impact['waterSaved'])->toBe(180.0);
});
