<?php

use App\Services\OrderWasteStreamReportingService;

/**
 * Verify that the "Circularity (Reuse)" classification (slug = 'recovery') is
 * routed to the Avoidance bucket and NOT conflated with the Recovered bucket
 * (slug = 'recovered'). This was previously broken: both slugs landed in Recovery.
 */
it('routes circularity (slug=recovery) to avoidance and not to recovery', function () {
    $service = app(OrderWasteStreamReportingService::class);

    $circularity = (object) [
        'total_weight' => 500,
        'material' => (object) [
            'classification_id' => 1,
            'classification' => (object) ['slug' => 'recovery'],
        ],
    ];

    $materialRecovery = (object) [
        'total_weight' => 300,
        'material' => (object) [
            'classification_id' => 2,
            'classification' => (object) ['slug' => 'recovered'],
        ],
    ];

    $recycling = (object) [
        'total_weight' => 200,
        'material' => (object) [
            'classification_id' => 3,
            'classification' => (object) ['slug' => 'recycling'],
        ],
    ];

    $totals = $service->classificationTotalsFromSummaries(
        collect([$circularity, $materialRecovery, $recycling])
    );

    expect($totals['avoidance']['total'])->toBe(500.0)
        ->and($totals['recovery']['total'])->toBe(300.0)
        ->and($totals['recycling']['total'])->toBe(200.0)
        ->and($totals['diverted']['total'])->toBe(1000.0);
});

it('material recovery and organics (both slug=recovered) accumulate in recovery bucket', function () {
    $service = app(OrderWasteStreamReportingService::class);

    $organics = (object) [
        'total_weight' => 150,
        'material' => (object) [
            'classification_id' => 2,
            'classification' => (object) ['slug' => 'recovered'],
        ],
    ];

    $nonOrganicRecovered = (object) [
        'total_weight' => 350,
        'material' => (object) [
            'classification_id' => 2,
            'classification' => (object) ['slug' => 'recovered'],
        ],
    ];

    $totals = $service->classificationTotalsFromSummaries(collect([$organics, $nonOrganicRecovered]));

    expect($totals['recovery']['total'])->toBe(500.0)
        ->and($totals['avoidance']['total'])->toBe(0.0);
});
