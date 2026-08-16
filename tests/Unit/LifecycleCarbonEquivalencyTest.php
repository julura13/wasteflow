<?php

use App\Services\LifecycleCarbonEquivalency;

test('matches docs/Dashboard & Reports - Metrics example lifecycle 393.84 kg CO₂e', function () {
    $service = new LifecycleCarbonEquivalency;
    $e = $service->fromLifecycleSavingKgCo2e(393.84);

    expect($e['electricityEquivalentKwhSaGrid'])->toBe(437.6);
    expect($e['transportEquivalentKm'])->toBe(2051.25);
    expect($e['fuelEquivalentLitresPetrol'])->toBe(170.49);
    expect($e['carsOffRoadAnnualEquivalent'])->toBe(0.0856);
});

test('barrels of oil and homes powered use EPA Greenhouse Gas Equivalencies Calculator factors', function () {
    $service = new LifecycleCarbonEquivalency;
    $e = $service->fromLifecycleSavingKgCo2e(393.84);

    expect($e['barrelsOfOilSaved'])->toBe(0.91);     // 393.84 ÷ 431.9 kg CO2e/barrel
    expect($e['homesPoweredOneMonth'])->toBe(0.99);  // 393.84 ÷ 399.83 kg CO2e/home/month
});

test('returns zeros for non-positive lifecycle', function () {
    $service = new LifecycleCarbonEquivalency;
    $e = $service->fromLifecycleSavingKgCo2e(0);

    expect($e['electricityEquivalentKwhSaGrid'])->toBe(0.0);
    expect($e['transportEquivalentKm'])->toBe(0.0);
    expect($e['barrelsOfOilSaved'])->toBe(0.0);
    expect($e['homesPoweredOneMonth'])->toBe(0.0);
});
