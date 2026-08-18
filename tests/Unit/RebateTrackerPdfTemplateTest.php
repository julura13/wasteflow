<?php

use Tests\TestCase;

uses(TestCase::class);

it('renders rebate tracker pdf with calendar dates and summary before the table', function () {
    $html = view('reports.rebate-tracker-pdf', [
        'filters' => [
            'start_date' => '2026-01-05',
            'end_date' => '2026-02-10',
            'company_name' => 'Acme',
            'branch_name' => 'HQ',
            'site_name' => 'Site 1',
        ],
        'rebateData' => [
            [
                'date' => '2026-03-15',
                'company_name' => 'Acme',
                'branch_name' => 'HQ',
                'site_name' => 'Site 1',
                'tracking_numbers' => 'T1',
                'grade' => 'HL 1',
                'weight' => 10.5,
                'rate' => 2,
                'total' => 21,
            ],
        ],
        'totalWeight' => 10.5,
        'totalRebate' => 21,
        'canViewProvider' => true,
    ])->render();

    expect($html)->toContain('2026/01/05');
    expect($html)->toContain('2026/02/10');
    expect($html)->toContain('2026/03/15');
    expect($html)->toContain('Acme - HQ - Site 1');
    expect($html)->toContain('font-size: 14px;');

    $summaryPos = strpos($html, 'summary-box');
    $tablePos = strpos($html, '<table');
    expect($summaryPos !== false && $tablePos !== false && $summaryPos < $tablePos)->toBeTrue();
});

it('shows the Provider column when canViewProvider is true', function () {
    $html = view('reports.rebate-tracker-pdf', [
        'filters' => ['start_date' => '2026-01-05', 'end_date' => '2026-02-10'],
        'rebateData' => [[
            'date' => '2026-03-15', 'company_name' => 'Acme', 'branch_name' => 'HQ', 'site_name' => 'Site 1',
            'tracking_numbers' => 'T1', 'grade' => 'HL 1', 'service_provider_name' => 'CL Trading',
            'weight' => 10.5, 'rate' => 2, 'total' => 21,
        ]],
        'totalWeight' => 10.5,
        'totalRebate' => 21,
        'canViewProvider' => true,
    ])->render();

    expect($html)->toContain('<th>Provider</th>')
        ->and($html)->toContain('CL Trading');
});

it('hides the Provider column and provider names when canViewProvider is false, since clients never see which provider handled a load', function () {
    $html = view('reports.rebate-tracker-pdf', [
        'filters' => ['start_date' => '2026-01-05', 'end_date' => '2026-02-10'],
        'rebateData' => [[
            'date' => '2026-03-15', 'company_name' => 'Acme', 'branch_name' => 'HQ', 'site_name' => 'Site 1',
            'tracking_numbers' => 'T1', 'grade' => 'HL 1', 'service_provider_name' => 'CL Trading',
            'weight' => 10.5, 'rate' => 2, 'total' => 21,
        ]],
        'totalWeight' => 10.5,
        'totalRebate' => 21,
        'canViewProvider' => false,
    ])->render();

    expect($html)->not->toContain('<th>Provider</th>')
        ->and($html)->not->toContain('CL Trading');
});
