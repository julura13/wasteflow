<?php

uses(Tests\TestCase::class);

it('renders rebate tracker pdf with south african d/m/y dates', function () {
    $html = view('reports.rebate-tracker-pdf', [
        'filters' => [
            'start_date' => '2026-01-05',
            'end_date' => '2026-02-10',
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
    ])->render();

    expect($html)->toContain('05/01/2026');
    expect($html)->toContain('10/02/2026');
    expect($html)->toContain('15/03/2026');
});
