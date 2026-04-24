<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChartImageService
{
    private const QUICKCHART_API_URL = 'https://quickchart.io/chart';

    private const DEFAULT_WIDTH = 900;

    private const DEFAULT_HEIGHT = 450;

    /**
     * Generate a bar chart image
     *
     * @param  array  $config  Chart configuration
     * @param  string  $filename  Output filename
     * @return string|null Path to saved image or null on failure
     */
    public function generateBarChart(array $config, string $filename): ?string
    {
        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $config['labels'] ?? [],
                'datasets' => $this->formatBarDatasets($config['datasets'] ?? []),
            ],
            'options' => $this->mergeChartOptions([
                'plugins' => [
                    'title' => [
                        'display' => ! empty($config['title']),
                        'text' => $config['title'] ?? '',
                        'font' => ['size' => 18, 'weight' => 'bold'],
                    ],
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                    'datalabels' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'font' => ['size' => 12],
                        ],
                        'grid' => [
                            'color' => '#e5e7eb',
                        ],
                    ],
                    'x' => [
                        'ticks' => [
                            'font' => ['size' => 12],
                        ],
                        'grid' => [
                            'display' => false,
                        ],
                    ],
                ],
                'animation' => false,
                'responsive' => true,
                'maintainAspectRatio' => false,
            ], $config['options'] ?? []),
        ];

        return $this->generateChart($chartConfig, $filename, $config['width'] ?? self::DEFAULT_WIDTH, $config['height'] ?? self::DEFAULT_HEIGHT);
    }

    /**
     * Generate a stacked bar chart image
     *
     * @param  array  $config  Chart configuration
     * @param  string  $filename  Output filename
     * @return string|null Path to saved image or null on failure
     */
    public function generateStackedBarChart(array $config, string $filename): ?string
    {
        $indexAxis = $config['horizontal'] ?? false ? 'y' : 'x';

        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $config['labels'] ?? [],
                'datasets' => $this->formatBarDatasets($config['datasets'] ?? [], true),
            ],
            'options' => $this->mergeChartOptions([
                'indexAxis' => $indexAxis,
                'plugins' => [
                    'title' => [
                        'display' => ! empty($config['title']),
                        'text' => $config['title'] ?? '',
                        'font' => ['size' => 18, 'weight' => 'bold'],
                    ],
                    'legend' => [
                        'display' => true,
                        'position' => $config['legendPosition'] ?? 'right',
                    ],
                    'datalabels' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'x' => [
                        'stacked' => true,
                        'beginAtZero' => true,
                        'ticks' => [
                            'font' => ['size' => 12],
                        ],
                        'grid' => [
                            'color' => '#e5e7eb',
                            'display' => $indexAxis === 'y',
                        ],
                    ],
                    'y' => [
                        'stacked' => true,
                        'beginAtZero' => true,
                        'ticks' => [
                            'font' => ['size' => 12],
                        ],
                        'grid' => [
                            'color' => '#e5e7eb',
                            'display' => $indexAxis === 'x',
                        ],
                    ],
                ],
                'animation' => false,
                'responsive' => true,
                'maintainAspectRatio' => false,
            ], $config['options'] ?? []),
        ];

        return $this->generateChart($chartConfig, $filename, $config['width'] ?? self::DEFAULT_WIDTH, $config['height'] ?? self::DEFAULT_HEIGHT);
    }

    /**
     * Generate a pie chart image
     *
     * @param  array  $config  Chart configuration
     * @param  string  $filename  Output filename
     * @return string|null Path to saved image or null on failure
     */
    public function generatePieChart(array $config, string $filename): ?string
    {
        $chartConfig = [
            'type' => 'pie',
            'data' => [
                'labels' => $config['labels'] ?? [],
                'datasets' => [[
                    'data' => $config['data'] ?? [],
                    'backgroundColor' => $config['colors'] ?? $this->getDefaultColors(count($config['data'] ?? [])),
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ]],
            ],
            'options' => $this->mergeChartOptions([
                'plugins' => [
                    'title' => [
                        'display' => ! empty($config['title']),
                        'text' => $config['title'] ?? '',
                        'font' => ['size' => 18, 'weight' => 'bold'],
                    ],
                    'legend' => [
                        'display' => true,
                        'position' => $config['legendPosition'] ?? 'right',
                    ],
                    'tooltip' => [
                        'enabled' => true,
                    ],
                    'datalabels' => [
                        'display' => false,
                    ],
                ],
                'animation' => false,
                'responsive' => true,
                'maintainAspectRatio' => false,
            ], $config['options'] ?? []),
        ];

        return $this->generateChart($chartConfig, $filename, $config['width'] ?? self::DEFAULT_WIDTH, $config['height'] ?? self::DEFAULT_HEIGHT);
    }

    /**
     * Generate a doughnut chart image
     *
     * @param  array  $config  Chart configuration
     * @param  string  $filename  Output filename
     * @return string|null Path to saved image or null on failure
     */
    public function generateDoughnutChart(array $config, string $filename): ?string
    {
        $chartConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $config['labels'] ?? [],
                'datasets' => [[
                    'data' => $config['data'] ?? [],
                    'backgroundColor' => $config['colors'] ?? $this->getDefaultColors(count($config['data'] ?? [])),
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ]],
            ],
            'options' => $this->mergeChartOptions([
                'plugins' => [
                    'title' => [
                        'display' => ! empty($config['title']),
                        'text' => $config['title'] ?? '',
                        'font' => ['size' => 18, 'weight' => 'bold'],
                    ],
                    'legend' => [
                        'display' => true,
                        'position' => $config['legendPosition'] ?? 'bottom',
                    ],
                    'tooltip' => [
                        'enabled' => true,
                    ],
                    'datalabels' => [
                        'display' => false,
                    ],
                ],
                'animation' => false,
                'responsive' => true,
                'maintainAspectRatio' => false,
                'cutout' => $config['cutout'] ?? '60%',
            ], $config['options'] ?? []),
        ];

        return $this->generateChart($chartConfig, $filename, $config['width'] ?? self::DEFAULT_WIDTH, $config['height'] ?? self::DEFAULT_HEIGHT);
    }

    /**
     * Deep-merge chart options so callers can override single plugin keys without dropping defaults.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function mergeChartOptions(array $defaults, array $overrides): array
    {
        return array_replace_recursive($defaults, $overrides);
    }

    /**
     * Generate chart image via QuickChart API
     *
     * @param  array  $chartConfig  Chart.js configuration
     * @param  string  $filename  Output filename
     * @param  int  $width  Image width
     * @param  int  $height  Image height
     * @return string|null Path to saved image or null on failure
     */
    private function generateChart(array $chartConfig, string $filename, int $width, int $height): ?string
    {
        try {
            $response = Http::timeout(30)->post(self::QUICKCHART_API_URL, [
                'chart' => json_encode($chartConfig),
                'width' => $width,
                'height' => $height,
                'backgroundColor' => 'white',
                'format' => 'png',
            ]);

            if ($response->successful()) {
                $imageData = $response->body();
                $path = 'charts/'.$filename;

                Storage::disk('public')->put($path, $imageData);

                return Storage::disk('public')->url($path);
            }

            Log::error('QuickChart API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Chart generation failed', [
                'error' => $e->getMessage(),
                'filename' => $filename,
            ]);

            return null;
        }
    }

    /**
     * Format bar chart datasets with Excel-style 3D appearance
     */
    private function formatBarDatasets(array $datasets, bool $stacked = false): array
    {
        return array_map(function ($dataset) {
            return [
                'label' => $dataset['label'] ?? '',
                'data' => $dataset['data'] ?? [],
                'backgroundColor' => $this->addDepthToColor($dataset['backgroundColor'] ?? '#3b82f6'),
                'borderColor' => $this->darkenColor($dataset['backgroundColor'] ?? '#3b82f6'),
                'borderWidth' => 2,
                'borderSkipped' => false,
            ];
        }, $datasets);
    }

    /**
     * Add depth/shadow effect to color (Excel-style 3D look)
     *
     * @param  string  $color  Hex color
     * @return string|array Gradient or color
     */
    private function addDepthToColor(string $color): string
    {
        // For now, return the color as-is. QuickChart supports gradients via canvas context
        // We'll use a slightly darker border for depth effect
        return $color;
    }

    /**
     * Darken a color for borders
     *
     * @param  string  $color  Hex color
     * @param  float  $factor  Darkening factor (0.0 to 1.0, where 0.4 = darken by 40%)
     * @return string Darkened hex color
     */
    private function darkenColor(string $color, float $factor = 0.2): string
    {
        $color = ltrim($color, '#');
        $rgb = [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        ];

        // Darken by factor
        $multiplier = 1.0 - $factor;
        $rgb = array_map(function ($val) use ($multiplier) {
            return max(0, min(255, floor($val * $multiplier)));
        }, $rgb);

        return '#'.str_pad(dechex($rgb[0]), 2, '0', STR_PAD_LEFT).
                   str_pad(dechex($rgb[1]), 2, '0', STR_PAD_LEFT).
                   str_pad(dechex($rgb[2]), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get default color palette
     *
     * @param  int  $count  Number of colors needed
     */
    private function getDefaultColors(int $count): array
    {
        $colors = [
            '#3b82f6', // Blue
            '#22c55e', // Green
            '#f59e0b', // Amber
            '#ef4444', // Red
            '#8b5cf6', // Purple
            '#06b6d4', // Cyan
            '#f97316', // Orange
            '#84cc16', // Lime
        ];

        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $colors[$i % count($colors)];
        }

        return $result;
    }
}
