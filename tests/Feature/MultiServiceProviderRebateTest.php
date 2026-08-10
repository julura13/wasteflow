<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
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

function createRebateFixtures(): array
{
    $company = Company::create(['name' => 'Multi Provider Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $providerA = ServiceProvider::create(['name' => 'Provider A', 'is_active' => true]);
    $providerB = ServiceProvider::create(['name' => 'Provider B', 'is_active' => true]);

    $facility = \App\Models\Facility::firstOrCreate(['name' => 'MSP Facility'], ['facility_type' => 'recycling', 'is_active' => true]);
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Paper'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'MSP Grade'], ['is_active' => true]);

    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
        'rebate_rate' => 3,
        'client_rebate_share' => 100,
    ]);

    return compact('company', 'branch', 'site', 'providerA', 'providerB', 'material');
}

it('defaults a saved load to the order\'s own service provider when none is specified', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    ['company' => $company, 'branch' => $branch, 'site' => $site, 'providerA' => $providerA, 'material' => $material] = createRebateFixtures();

    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $providerA->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'documents_required',
        'tracking_number' => 'RO-MSP-001',
        'requested_collection_date' => now(),
    ]);

    $this->actingAs($user)->postJson(route('orders.save-weights', $order->id), [
        'weight_lines' => [
            ['material_id' => $material->id, 'weight' => 50],
        ],
    ])->assertOk();

    $stream = OrderWasteStream::where('order_id', $order->id)->first();
    expect($stream->service_provider_id)->toBe($providerA->id);
});

it('lets a load specify a different service provider than the order default', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    ['company' => $company, 'branch' => $branch, 'site' => $site, 'providerA' => $providerA, 'providerB' => $providerB, 'material' => $material] = createRebateFixtures();

    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $providerA->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'documents_required',
        'tracking_number' => 'RO-MSP-002',
        'requested_collection_date' => now(),
    ]);

    $this->actingAs($user)->postJson(route('orders.save-weights', $order->id), [
        'weight_lines' => [
            ['material_id' => $material->id, 'weight' => 50, 'service_provider_id' => $providerB->id],
        ],
    ])->assertOk();

    $stream = OrderWasteStream::where('order_id', $order->id)->first();
    expect($stream->service_provider_id)->toBe($providerB->id);
});

it('breaks rebate totals out per service provider when a finalized order has loads from more than one provider', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    ['company' => $company, 'branch' => $branch, 'site' => $site, 'providerA' => $providerA, 'providerB' => $providerB, 'material' => $material] = createRebateFixtures();

    $collectionDate = Carbon::parse('2026-05-15');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $providerA->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-MSP-003',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'service_provider_id' => $providerA->id,
        'gross_weight' => 40,
        'nett_weight' => 40,
        'rebate_rate' => 3,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'service_provider_id' => $providerB->id,
        'gross_weight' => 60,
        'nett_weight' => 60,
        'rebate_rate' => 3,
    ]);

    $response = $this->actingAs($user)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
    ]));

    $response->assertOk();

    $providerBreakdown = collect($response->viewData('page')['props']['providerBreakdown'])->keyBy('provider_name');

    expect($providerBreakdown->has('Provider A'))->toBeTrue();
    expect($providerBreakdown->has('Provider B'))->toBeTrue();
    expect((float) $providerBreakdown['Provider A']['weight'])->toBe(40.0);
    expect((float) $providerBreakdown['Provider A']['total'])->toBe(120.0);
    expect((float) $providerBreakdown['Provider B']['weight'])->toBe(60.0);
    expect((float) $providerBreakdown['Provider B']['total'])->toBe(180.0);
});

it('hides the provider breakdown from a client-role user even though the rebate data itself is scoped and visible', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    ['company' => $company, 'branch' => $branch, 'site' => $site, 'providerA' => $providerA, 'providerB' => $providerB, 'material' => $material] = createRebateFixtures();

    $client = User::factory()->create();
    $client->assignRole('client');
    $client->companies()->attach($company->id);

    $collectionDate = Carbon::parse('2026-07-15');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $providerA->id,
        'created_by' => $admin->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-MSP-005',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'service_provider_id' => $providerA->id,
        'gross_weight' => 40,
        'nett_weight' => 40,
        'rebate_rate' => 3,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'service_provider_id' => $providerB->id,
        'gross_weight' => 60,
        'nett_weight' => 60,
        'rebate_rate' => 3,
    ]);

    $response = $this->actingAs($client)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]));

    $response->assertOk();

    $props = $response->viewData('page')['props'];

    // The client still sees their own rebate totals...
    expect(collect($props['rebateData'])->sum('weight'))->toBe(100.0);
    // ...but never the internal per-provider breakdown.
    expect($props['providerBreakdown'])->toBe([]);
});

it('keeps two service providers that share a name as separate rows instead of merging their totals', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    ['company' => $company, 'branch' => $branch, 'site' => $site, 'material' => $material] = createRebateFixtures();

    // Two distinct provider records that happen to share a name - nothing in the schema
    // prevents this, and the rebate report must not silently combine their totals.
    $providerX = ServiceProvider::create(['name' => 'Shared Name Co', 'is_active' => true]);
    $providerY = ServiceProvider::create(['name' => 'Shared Name Co', 'is_active' => true]);

    $collectionDate = Carbon::parse('2026-06-15');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $providerX->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-MSP-004',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'service_provider_id' => $providerX->id,
        'gross_weight' => 40,
        'nett_weight' => 40,
        'rebate_rate' => 3,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'service_provider_id' => $providerY->id,
        'gross_weight' => 60,
        'nett_weight' => 60,
        'rebate_rate' => 3,
    ]);

    $response = $this->actingAs($user)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
    ]));

    $response->assertOk();

    $providerBreakdown = collect($response->viewData('page')['props']['providerBreakdown']);
    $sharedNameRows = $providerBreakdown->where('provider_name', 'Shared Name Co');

    // Two separate rows for the two providers, not one row with the totals merged.
    expect($sharedNameRows)->toHaveCount(2);
    expect($sharedNameRows->pluck('weight')->sort()->values()->all())->toBe([40.0, 60.0]);
});
