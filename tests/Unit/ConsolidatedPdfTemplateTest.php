<?php

use App\Models\Order;
use App\Models\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

it('includes semibold styling for order number site name and order details in consolidated pdf template', function () {
    $html = view('orders.consolidated-pdf', [
        'orders' => new Collection,
        'serviceProvider' => new ServiceProvider(['name' => 'Test Provider']),
        'collectionDate' => Carbon::parse('2026-03-30'),
    ])->render();

    expect($html)
        ->toContain('.consolidated-table td.order-number')
        ->toContain('.consolidated-table td.site-name')
        ->toContain('.consolidated-table td.order-details')
        ->toContain('font-weight: 600');
});

it('wraps order number site name and order details cells when orders exist', function () {
    $order = new Order([
        'tracking_number' => 'RO-2603-31352',
        'quantity_lines' => [
            ['quantity_type' => 'loose_bags', 'quantity' => 35],
        ],
    ]);
    $order->setRelation('site', null);
    $order->setRelation('company', null);
    $order->setRelation('branch', null);

    $html = view('orders.consolidated-pdf', [
        'orders' => collect([$order]),
        'serviceProvider' => new ServiceProvider(['name' => 'CL Trading']),
        'collectionDate' => Carbon::parse('2026-03-30'),
    ])->render();

    expect($html)
        ->toMatch('/<td class="order-number">/')
        ->toMatch('/<td class="site-name">/')
        ->toMatch('/<td class="order-details">/');
});
