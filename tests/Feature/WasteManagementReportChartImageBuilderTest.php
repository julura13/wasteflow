<?php

use App\Services\WasteManagementReportChartImageBuilder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('pre-generates all chart image paths from report data', function () {
    Storage::fake('public');

    Http::fake(function () {
        return Http::response("\x89PNG\r\n\x1a\n", 200, [
            'Content-Type' => 'image/png',
        ]);
    });

    $reportData = [
        'wasteStreamTotals' => [
            ['name' => 'Paper', 'value' => 100, 'color' => '#0000ff'],
        ],
        'classificationTotals' => [
            'avoidance' => ['percentage' => 10, 'total' => 1],
            'recycling' => ['percentage' => 20, 'total' => 2],
            'recovery' => ['percentage' => 30, 'total' => 3],
            'disposal' => ['percentage' => 40, 'total' => 4],
            'diverted' => ['percentage' => 0, 'total' => 0],
        ],
    ];

    $paths = app(WasteManagementReportChartImageBuilder::class)->buildForReportData($reportData);

    expect($paths)->toHaveKeys([
        'page1_waste_stream_pie',
        'page1_donut_avoidance',
        'page1_donut_recycling',
        'page1_donut_recovery',
        'page1_donut_disposal',
        'page1_donut_diverted',
    ]);

    foreach ($paths as $key => $url) {
        expect($url)->not->toBeNull("Missing chart path for {$key}");
    }

    // One file per successful QuickChart call (1 pie + 5 doughnuts = 6)
    expect(Storage::disk('public')->allFiles('charts'))->toHaveCount(6);
});
