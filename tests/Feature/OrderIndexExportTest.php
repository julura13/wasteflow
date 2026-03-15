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

it('filters orders by search across tracking number, company, branch, site and service provider', function () {
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

    $response = $this->actingAs($user)->get(route('orders.index', ['search' => 'Warehouse']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 1));

    $response = $this->actingAs($user)->get(route('orders.index', ['search' => 'nonexistent']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('orders.data', 0));
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
