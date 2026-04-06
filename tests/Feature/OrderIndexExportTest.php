<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
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

it('exports filtered orders as PDF', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Export Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

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

    $response = $this->actingAs($user)->get(route('orders.export.pdf'));
    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('exports filtered orders as CSV', function () {
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
    ]);

    $response = $this->actingAs($user)->get(route('orders.export.csv'));
    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv');
    $response->assertSee('Tracking Number');
    $response->assertSee('WO-2501-30099');
    $response->assertSee('Recycling Order');
});
