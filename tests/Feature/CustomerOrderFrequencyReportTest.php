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
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('shows customer order frequencies split by waste and recycling when user has view-reports-all', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-06 12:00:00'));

    $admin = User::factory()->create();
    $admin->assignRole('manager');

    $companyA = Company::create(['name' => 'Alpha Ltd', 'is_active' => true]);
    $companyB = Company::create(['name' => 'Beta Pty', 'is_active' => true]);

    $branchA = Branch::create(['company_id' => $companyA->id, 'name' => 'A1', 'is_active' => true]);
    $siteA = Site::create(['branch_id' => $branchA->id, 'name' => 'Site A', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'Prov', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-2601-30001',
        'company_id' => $companyA->id,
        'branch_id' => $branchA->id,
        'site_id' => $siteA->id,
        'service_provider_id' => $provider->id,
        'created_by' => $admin->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-01-10'),
        'actual_collection_date' => Carbon::parse('2026-03-27'),
    ]);

    Order::create([
        'tracking_number' => 'WO-2601-30002',
        'company_id' => $companyA->id,
        'branch_id' => $branchA->id,
        'site_id' => $siteA->id,
        'service_provider_id' => $provider->id,
        'created_by' => $admin->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-02-01'),
        'actual_collection_date' => Carbon::parse('2026-04-01'),
    ]);

    Order::create([
        'tracking_number' => 'RO-2604-30001',
        'company_id' => $companyA->id,
        'branch_id' => $branchA->id,
        'site_id' => $siteA->id,
        'service_provider_id' => $provider->id,
        'created_by' => $admin->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-03-01'),
        'actual_collection_date' => Carbon::parse('2026-03-28'),
    ]);

    $branchB = Branch::create(['company_id' => $companyB->id, 'name' => 'B1', 'is_active' => true]);
    $siteB = Site::create(['branch_id' => $branchB->id, 'name' => 'Site B', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-2601-30003',
        'company_id' => $companyB->id,
        'branch_id' => $branchB->id,
        'site_id' => $siteB->id,
        'service_provider_id' => $provider->id,
        'created_by' => $admin->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-03-01'),
        'actual_collection_date' => Carbon::parse('2025-03-01'),
    ]);

    $response = $this->actingAs($admin)->get(route('reports.customer-order-frequencies', ['lookback_months' => 12]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/CustomerOrderFrequencies')
        ->where('lookback_months', 12)
        ->has('rows', 2)
        ->where('rows.0.company_name', 'Alpha Ltd')
        ->where('rows.0.waste.last_finalized_date', '2026-04-01')
        ->where('rows.0.waste.days_since_last_finalized', 5)
        ->where('rows.0.waste.finalized_orders_in_period', 2)
        ->where('rows.0.waste.average_orders_per_month', 0.17)
        ->where('rows.0.recycling.last_finalized_date', '2026-03-28')
        ->where('rows.0.recycling.days_since_last_finalized', 9)
        ->where('rows.0.recycling.finalized_orders_in_period', 1)
        ->where('rows.0.recycling.average_orders_per_month', 0.08)
        ->where('rows.1.company_name', 'Beta Pty')
        ->where('rows.1.waste.last_finalized_date', '2025-03-01')
        ->where('rows.1.waste.finalized_orders_in_period', 0)
        ->where('rows.1.waste.average_orders_per_month', 0)
        ->where('rows.1.recycling.last_finalized_date', null)
        ->where('rows.1.recycling.days_since_last_finalized', null)
        ->where('rows.1.recycling.finalized_orders_in_period', 0)
        ->where('rows.1.recycling.average_orders_per_month', 0));

    Carbon::setTestNow();
});

it('scopes companies to the user company when client has no view-reports-all', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-06 12:00:00'));

    $companyA = Company::create(['name' => 'Alpha Ltd', 'is_active' => true]);
    $companyB = Company::create(['name' => 'Beta Pty', 'is_active' => true]);

    $client = User::factory()->create(['company_id' => $companyA->id]);
    $client->assignRole('client');

    $admin = User::factory()->create();
    $admin->assignRole('manager');
    $branchA = Branch::create(['company_id' => $companyA->id, 'name' => 'A1', 'is_active' => true]);
    $siteA = Site::create(['branch_id' => $branchA->id, 'name' => 'Site A', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'Prov', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-2601-40001',
        'company_id' => $companyA->id,
        'branch_id' => $branchA->id,
        'site_id' => $siteA->id,
        'service_provider_id' => $provider->id,
        'created_by' => $admin->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-01'),
        'actual_collection_date' => Carbon::parse('2026-04-05'),
    ]);

    $branchB = Branch::create(['company_id' => $companyB->id, 'name' => 'B1', 'is_active' => true]);
    $siteB = Site::create(['branch_id' => $branchB->id, 'name' => 'Site B', 'is_active' => true]);
    Order::create([
        'tracking_number' => 'WO-2601-40002',
        'company_id' => $companyB->id,
        'branch_id' => $branchB->id,
        'site_id' => $siteB->id,
        'service_provider_id' => $provider->id,
        'created_by' => $admin->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-01'),
        'actual_collection_date' => Carbon::parse('2026-04-04'),
    ]);

    $response = $this->actingAs($client)->get(route('reports.customer-order-frequencies'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('rows', 1)
        ->where('rows.0.company_name', 'Alpha Ltd'));

    Carbon::setTestNow();
});

it('exports customer order frequencies as csv', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-06 12:00:00'));

    $admin = User::factory()->create();
    $admin->assignRole('manager');

    $company = Company::create(['name' => 'Csv Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B1', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S1', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'Prov', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-CSV-30001',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $admin->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-01'),
        'actual_collection_date' => Carbon::parse('2026-04-02'),
    ]);

    $response = $this->actingAs($admin)->get(route('reports.customer-order-frequencies.export', ['lookback_months' => 6]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv');
    $response->assertSee('Customer');
    $response->assertSee('Waste last finalized');
    $response->assertSee('Recycling last finalized');
    $response->assertSee('Csv Co');
    $response->assertSee('6');

    Carbon::setTestNow();
});

it('exports customer order frequencies as pdf', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-06 12:00:00'));

    $admin = User::factory()->create();
    $admin->assignRole('manager');

    $company = Company::create(['name' => 'Pdf Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B1', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S1', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'Prov', 'is_active' => true]);

    Order::create([
        'tracking_number' => 'WO-PDF-30001',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $admin->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-01'),
        'actual_collection_date' => Carbon::parse('2026-04-02'),
    ]);

    $response = $this->actingAs($admin)->get(route('reports.customer-order-frequencies.export-pdf', ['lookback_months' => 3]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');

    Carbon::setTestNow();
});

it('rejects invalid lookback months', function () {
    $admin = User::factory()->create();
    $admin->assignRole('manager');

    $this->actingAs($admin)->get(route('reports.customer-order-frequencies', ['lookback_months' => 0]))
        ->assertSessionHasErrors('lookback_months');

    $this->actingAs($admin)->get(route('reports.customer-order-frequencies', ['lookback_months' => 99]))
        ->assertSessionHasErrors('lookback_months');
});

it('forbids users without view-reports', function () {
    $user = User::factory()->create();
    $user->assignRole('order_finalizer');

    $this->actingAs($user)->get(route('reports.customer-order-frequencies'))
        ->assertForbidden();

    $this->actingAs($user)->get(route('reports.customer-order-frequencies.export'))
        ->assertForbidden();

    $this->actingAs($user)->get(route('reports.customer-order-frequencies.export-pdf'))
        ->assertForbidden();
});
