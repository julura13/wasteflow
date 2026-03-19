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

test('returns zeros for non-positive lifecycle', function () {
    $service = new LifecycleCarbonEquivalency;
    $e = $service->fromLifecycleSavingKgCo2e(0);

    expect($e['electricityEquivalentKwhSaGrid'])->toBe(0.0);
    expect($e['transportEquivalentKm'])->toBe(0.0);
});
