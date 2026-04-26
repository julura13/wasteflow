<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class WasteManagementReportChartImageBuilder
{
    public function __construct(
        private readonly ChartImageService $chartService,
    ) {}

    /**
     * Pre-generate all QuickChart PNGs for the waste-management-pdf Blade template.
     * Run this (or inject the result) before building the PDF so the view receives populated `$chartPaths`.
     *
     * @param  array<string, mixed>  $reportData  Same structure as {@see \App\Http\Controllers\ReportController::getReportData} payload.
     * @return array<string, string|null> Absolute web paths suitable for `imageToBase64()` in the template (e.g. `/storage/charts/...`), or null if generation failed.
     */
    public function buildForReportData(array $reportData): array
    {
        $timestamp = now()->format('YmdHis');

        if (! Storage::disk('public')->exists('charts')) {
            Storage::disk('public')->makeDirectory('charts');
        }

        $wasteStreamRows = array_values(array_filter(
            $reportData['wasteStreamTotals'] ?? [],
            static fn (array $row): bool => ((float) ($row['value'] ?? 0)) > 0
        ));

        if ($wasteStreamRows === []) {
            $wasteStreamRows = [
                ['name' => 'No data', 'value' => 1.0, 'color' => '#e5e7eb'],
            ];
        }

        $chartPaths = [];

        $chartPaths['page1_waste_stream_pie'] = $this->chartService->generateDoughnutChart([
            'title' => '',
            'data' => array_column($wasteStreamRows, 'value'),
            'colors' => array_column($wasteStreamRows, 'color'),
            'cutout' => '0%',
            'width' => 520,
            'height' => 320,
            'options' => [
                'layout' => [
                    'padding' => 4,
                ],
                'plugins' => [
                    'legend' => [
                        'display' => false,
                    ],
                    'title' => [
                        'display' => false,
                    ],
                    'tooltip' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ], "page1_waste_stream_pie_{$timestamp}.png");

        $classification = $reportData['classificationTotals'] ?? [];
        $donutDefs = [
            'page1_donut_avoidance' => ['label' => 'AVOIDANCE', 'pct' => (float) ($classification['avoidance']['percentage'] ?? 0), 'color' => '#BDBDBD'],
            'page1_donut_recycling' => ['label' => 'RECYCLING', 'pct' => (float) ($classification['recycling']['percentage'] ?? 0), 'color' => '#6FCF97'],
            'page1_donut_recovery' => ['label' => 'RECOVERY', 'pct' => (float) ($classification['recovery']['percentage'] ?? 0), 'color' => '#2D9CDB'],
            'page1_donut_disposal' => ['label' => 'DISPOSAL', 'pct' => (float) ($classification['disposal']['percentage'] ?? 0), 'color' => '#1C1C1C'],
            'page1_donut_diverted' => ['label' => 'DIVERTED', 'pct' => (float) ($classification['diverted']['percentage'] ?? 0), 'color' => '#C69200'],
        ];

        foreach ($donutDefs as $key => $def) {
            $p = min(100.0, max(0.0, $def['pct']));
            $rest = $p >= 100.0 ? 0.0 : max(0.01, 100.0 - $p);
            $chartPaths[$key] = $this->chartService->generateDoughnutChart([
                'title' => '',
                'labels' => ['', ''],
                'data' => $p <= 0 && $rest >= 100 ? [0.0, 100.0] : [$p, $rest],
                'colors' => [$def['color'], '#e5e7eb'],
                'legendPosition' => 'bottom',
                'cutout' => '56%',
                'width' => 260,
                'height' => 260,
                'options' => [
                    'plugins' => [
                        'legend' => ['display' => false],
                        'title' => ['display' => false],
                        'tooltip' => [
                            'enabled' => false,
                        ],
                    ],
                ],
            ], "{$key}_{$timestamp}.png");
        }

        return $chartPaths;
    }
}
