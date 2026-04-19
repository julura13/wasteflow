<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\Site;
use App\Support\OrderExportFormatting;

uses(Tests\TestCase::class);

it('formats company branch site as one label', function () {
    $company = new Company(['name' => 'Acme']);
    $branch = new Branch(['name' => 'North']);
    $branch->setRelation('company', $company);
    $site = new Site(['name' => 'Dock']);
    $site->setRelation('branch', $branch);
    $order = new Order;
    $order->setRelation('site', $site);

    expect(OrderExportFormatting::companyBranchSite($order))->toBe('Acme / North / Dock');
});

it('formats quantity lines like the UI', function () {
    $order = new Order([
        'quantity_lines' => [
            ['quantity' => 5, 'container_option_name' => '240l Wheelie Bin', 'description' => 'General Waste'],
            ['quantity' => 1, 'container_option_name' => '30m3 Bin', 'description' => ''],
        ],
    ]);

    $text = OrderExportFormatting::collectionQuantities($order);
    expect($text)->toContain('5× 240l Wheelie Bin (General Waste)')
        ->and($text)->toContain('1× 30m3 Bin');
});

it('falls back to legacy quantity fields', function () {
    $order = new Order([
        'quantity_lines' => null,
        'quantity' => 10,
        'quantity_type' => 'loose_bags',
    ]);

    expect(OrderExportFormatting::collectionQuantities($order))->toBe('10× loose bags');
});
