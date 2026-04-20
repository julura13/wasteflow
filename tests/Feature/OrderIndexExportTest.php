<?php

use App\Jobs\GenerateOrderIndexExportJob;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderIndexExport;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('filters orders by search across tracking number, slip number, company, branch, site and service provider', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Acme Corp', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Warehouse Site', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'Green Waste Ltd', 'is_active' => true]);

    $order = Order::create([
        'tracking_number' => 'WO-2501-30001',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-15'),
    ]);

    $response = $this->actingAs($user)->get(route('orders.index', ['search' => 'Acme']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Orders/Index')
        ->has('orders.data', 1)
        ->where('orders.data.0.tracking_number', 'WO-2501-30001'));

    $response = $this->actingAs($user)->get(route('orders.index', ['search' => 'Green Waste']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1));

    $response = $this->actingAs($user)->get(route('orders.index', ['search' => '30001']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1));

    $order->update([
        'status' => 'finalized',
        'slip_number' => 'GW-2504-884422',
    ]);

    $response = $this->actingAs($user)->get(route('orders.index', ['search' => '884422']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1)
        ->where('orders.data.0.tracking_number', 'WO-2501-30001'));

    $response = $this->actingAs($user)->get(route('orders.index', ['search' => 'GW-2504']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1));

    $response = $this->actingAs($user)->get(route('orders.index', ['search' => 'Warehouse']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1));

    $response = $this->actingAs($user)->get(route('orders.index', ['search' => 'nonexistent']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 0));
});

it('includes quantity lines on the orders index payload for list display', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Qty Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-QTY-30001',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-15'),
        'quantity_lines' => [
            ['container_option_id' => 1, 'container_option_name' => 'Wheelie bin', 'quantity' => 2],
            ['container_option_id' => 2, 'container_option_name' => 'Skip 6m³', 'quantity' => 1],
        ],
        'estimated_quantity' => 3,
    ]);

    $response = $this->actingAs($user)->get(route('orders.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Orders/Index')
        ->has('orders.data', 1)
        ->where('orders.data.0.quantity_lines.0.container_option_name', 'Wheelie bin')
        ->where('orders.data.0.quantity_lines.0.quantity', 2)
        ->where('orders.data.0.quantity_lines.1.container_option_name', 'Skip 6m³')
        ->where('orders.data.0.quantity_lines.1.quantity', 1));
});

it('serializes collection dates on the orders index payload as calendar dates', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Date Payload Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-DATE-30001',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-21'),
        'actual_collection_date' => Carbon::parse('2026-04-22'),
    ]);

    $response = $this->actingAs($user)->get(route('orders.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Orders/Index')
        ->where('orders.data.0.tracking_number', 'WO-DATE-30001')
        ->where('orders.data.0.requested_collection_date', '2026-04-21')
        ->where('orders.data.0.actual_collection_date', '2026-04-22'));
});

it('filters orders by order type when one type is selected', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Br', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Si', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'Prov', 'is_active' => true]);

    Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-15'),
    ]);
    Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-16'),
    ]);

    $response = $this->actingAs($user)->get(route('orders.index', ['order_types' => ['waste']]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1)
        ->where('orders.data.0.order_type', 'waste'));

    $response = $this->actingAs($user)->get(route('orders.index', ['order_types' => ['recycling']]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1)
        ->where('orders.data.0.order_type', 'recycling'));

    $response = $this->actingAs($user)->get(route('orders.index', ['order_types' => ['waste', 'recycling']]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 2));
});

it('filters orders by requested collection date range', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Date Range Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-RANGE-A',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-06-01'),
    ]);
    Order::create([
        'tracking_number' => 'WO-RANGE-B',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-06-15'),
    ]);
    Order::create([
        'tracking_number' => 'WO-RANGE-C',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-07-01'),
    ]);

    $response = $this->actingAs($user)->get(route('orders.index', [
        'requested_collection_from' => '2025-06-10',
        'requested_collection_to' => '2025-06-20',
    ]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1)
        ->where('orders.data.0.tracking_number', 'WO-RANGE-B'));

    $response = $this->actingAs($user)->get(route('orders.index', [
        'requested_collection_from' => '2025-06-20',
        'requested_collection_to' => '2025-06-10',
    ]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1)
        ->where('orders.data.0.tracking_number', 'WO-RANGE-B'));
});

it('queues orders PDF export and allows download when ready', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Export Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-EXPORT-PDF-1',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-15'),
    ]);

    $response = $this->actingAs($user)->post(route('orders.export.request'), [
        'format' => 'pdf',
    ]);
    $response->assertRedirect();

    $export = OrderIndexExport::query()->first();
    expect($export)->not->toBeNull();
    expect($export->format)->toBe(OrderIndexExport::FORMAT_PDF);

    GenerateOrderIndexExportJob::dispatchSync($export->id);

    $export->refresh();
    expect($export->status)->toBe(OrderIndexExport::STATUS_COMPLETED);

    $download = $this->actingAs($user)->get(route('orders.export.download', $export->uuid));
    $download->assertOk();
    expect($download->headers->get('content-type'))->toContain('pdf');
});

it('queues orders CSV export and allows download when ready', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Export Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-2501-30099',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-15'),
        'quantity_lines' => [
            ['container_option_id' => 1, 'container_option_name' => '240l Wheelie Bin', 'quantity' => 3, 'description' => 'General Waste'],
        ],
        'estimated_quantity' => 3,
    ]);

    $this->actingAs($user)->post(route('orders.export.request'), [
        'format' => 'csv',
    ])->assertRedirect();

    $export = OrderIndexExport::query()->firstOrFail();
    GenerateOrderIndexExportJob::dispatchSync($export->id);
    $export->refresh();
    expect($export->status)->toBe(OrderIndexExport::STATUS_COMPLETED);

    $download = $this->actingAs($user)->get(route('orders.export.download', $export->uuid));
    $download->assertOk();
    expect($download->headers->get('content-type'))->toContain('csv');
    $csv = $download->streamedContent();
    expect($csv)->toContain('Tracking Number');
    expect($csv)->toContain('Company / Branch / Site');
    expect($csv)->toContain('Collection quantities');
    expect($csv)->toContain('WO-2501-30099');
    expect($csv)->toContain('Export Co / B / S');
    expect($csv)->toContain('Recycling Order');
    expect($csv)->toContain('3× 240l Wheelie Bin (General Waste)');
});

it('sorts queued CSV export rows by service provider name then newest order first', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Sort Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $zebra = ServiceProvider::create(['name' => 'Zebra Hauliers', 'is_active' => true]);
    $alpha = ServiceProvider::create(['name' => 'Alpha Logistics', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-ZEBRA-OLD',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $zebra->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-15'),
    ]);
    Order::create([
        'tracking_number' => 'WO-ALPHA-NEW',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $alpha->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-16'),
    ]);
    Order::create([
        'tracking_number' => 'WO-ALPHA-OLD',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $alpha->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-14'),
    ]);

    $this->actingAs($user)->post(route('orders.export.request'), ['format' => 'csv'])->assertRedirect();

    $export = OrderIndexExport::query()->firstOrFail();
    GenerateOrderIndexExportJob::dispatchSync($export->id);

    $download = $this->actingAs($user)->get(route('orders.export.download', $export->uuid));
    $download->assertOk();
    $content = $download->streamedContent();
    expect($content)->toContain('WO-ALPHA-NEW');
    expect($content)->toContain('WO-ALPHA-OLD');
    expect($content)->toContain('WO-ZEBRA-OLD');

    $posAlphaNew = strpos($content, 'WO-ALPHA-NEW');
    $posAlphaOld = strpos($content, 'WO-ALPHA-OLD');
    $posZebra = strpos($content, 'WO-ZEBRA-OLD');
    expect($posAlphaNew)->not->toBeFalse();
    expect($posAlphaOld)->not->toBeFalse();
    expect($posZebra)->not->toBeFalse();
    expect($posAlphaNew)->toBeLessThan($posAlphaOld);
    expect($posAlphaOld)->toBeLessThan($posZebra);
});

it('omits the service provider column from CSV when hide_service_provider is true', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Export Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'Hidden Provider', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-HIDE-SP-1',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-15'),
    ]);

    $this->actingAs($user)->post(route('orders.export.request'), [
        'format' => 'csv',
        'hide_service_provider' => true,
    ])->assertRedirect();

    $export = OrderIndexExport::query()->firstOrFail();
    expect($export->filters['hide_service_provider'] ?? false)->toBeTrue();

    GenerateOrderIndexExportJob::dispatchSync($export->id);
    $export->refresh();
    expect($export->status)->toBe(OrderIndexExport::STATUS_COMPLETED);

    $download = $this->actingAs($user)->get(route('orders.export.download', $export->uuid));
    $download->assertOk();
    $csv = $download->streamedContent();
    expect($csv)->not->toContain('Service Provider');
    expect($csv)->toContain('WO-HIDE-SP-1');
    expect($csv)->not->toContain('Hidden Provider');
});
