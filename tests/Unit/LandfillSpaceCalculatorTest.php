<?php

use App\Services\LandfillSpaceCalculator;

test('matches docs/Landfill space saved m3.xlsx sample rows', function () {
    $calculator = new LandfillSpaceCalculator;

    $weights = [
        'paper' => 100,
        'plastics' => 65,
        'aluminium' => 50,
        'steel' => 45,
        'glass' => 900,
        'tetrapak' => 150,
        'organics' => 45,
    ];

    $result = $calculator->calculate($weights);

    expect($result['paper']['spaceSaved'])->toBe(1.0);
    expect($result['plastics']['spaceSaved'])->toBe(1.0);
    expect($result['aluminium']['spaceSaved'])->toBe(0.04);
    expect($result['steel']['spaceSaved'])->toBe(0.15);
    expect($result['glass']['spaceSaved'])->toBe(2.25);
    expect($result['tetrapak']['spaceSaved'])->toBe(1.0);
    expect($result['organics']['spaceSaved'])->toBe(0.09);
    expect($result['total'])->toBe(5.53);
});

test('includes density metadata per category', function () {
    $calculator = new LandfillSpaceCalculator;
    $result = $calculator->calculate(['paper' => 100]);

    expect($result['paper']['densityKgPerM3'])->toBe(100.0);
    expect($result['plastics']['densityKgPerM3'])->toBe(65.0);
    expect($result['aluminium']['densityKgPerM3'])->toBe(1300.0);
});

test('wood uses 250 kg/m3 density factor', function () {
    $calculator = new LandfillSpaceCalculator;
    $result = $calculator->calculate(['wood' => 1000]);

    expect($result['wood']['densityKgPerM3'])->toBe(250.0);
    expect($result['wood']['spaceSaved'])->toBe(4.0); // 1000 ÷ 250
    expect($result['total'])->toBe(4.0);
});
