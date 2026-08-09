<?php

uses(Tests\TestCase::class);

it('renders the waste stream collection pdf with headings, subtotals, and no pricing', function () {
    $html = view('reports.waste-stream-collection-pdf', [
        'filters' => [
            'start_date' => '2026-01-05',
            'end_date' => '2026-02-10',
            'company_name' => 'Acme',
            'branch_name' => 'HQ',
            'site_name' => 'Site 1',
        ],
        'totalWeight' => 12.67,
        'wasteStreamBreakdown' => [
            [
                'waste_stream' => 'Plastic',
                'grade' => 'Film LD Clear',
                'heading' => 'Plastic - Film LD Clear',
                'rows' => [
                    [
                        'date' => '2026-02-02',
                        'tracking_number' => 'RO-2607-30056',
                        'slip_number' => '3422907',
                        'quantity' => '1× Bale',
                        'weight' => 6.34,
                    ],
                    [
                        'date' => '2026-02-12',
                        'tracking_number' => 'RO-2607-30057',
                        'slip_number' => '3422918',
                        'quantity' => '1× Bale',
                        'weight' => 6.33,
                    ],
                ],
                'subtotal_weight' => 12.67,
            ],
        ],
    ])->render();

    expect($html)->toContain('WASTE STREAM COLLECTION REPORT');
    expect($html)->toContain('Acme - HQ - Site 1');
    expect($html)->toContain('2026/01/05');
    expect($html)->toContain('2026/02/10');
    expect($html)->toContain('Plastic - Film LD Clear');
    expect($html)->toContain('RO-2607-30056');
    expect($html)->toContain('3422907');
    expect($html)->toContain('1× Bale');
    expect($html)->toContain('12.67');
    expect($html)->toContain('.breakdown-table tfoot td.text-right');
    expect($html)->not->toContain('Rate (R/kg)')
        ->not->toContain('Total (R)')
        ->not->toContain('Total Rebate');

    $summaryPos = strpos($html, 'summary-box');
    $breakdownPos = strpos($html, 'Plastic - Film LD Clear');
    expect($summaryPos !== false && $breakdownPos !== false && $summaryPos < $breakdownPos)->toBeTrue();
});

it('shows a fallback message when there is no waste stream collection data', function () {
    $html = view('reports.waste-stream-collection-pdf', [
        'filters' => [
            'start_date' => '2026-01-05',
            'end_date' => '2026-02-10',
        ],
        'totalWeight' => 0,
        'wasteStreamBreakdown' => [],
    ])->render();

    expect($html)->toContain('No waste stream collection data found for the selected filters.');
});
