<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
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

it('exposes diverted classification totals as non-disposal weight on the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Diverted Test Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'Provider DT', 'is_active' => true]);

    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Paper'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'Mixed Paper'], ['is_active' => true]);
    $classificationRecycling = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $classificationDisposal = \App\Models\Classification::firstOrCreate(['name' => 'Disposal'], ['is_active' => true]);
    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility DT'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );

    $materialRecycling = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classificationRecycling->id,
        'facility_id' => $facility->id,
        'is_active' => true,
    ]);

    $materialDisposal = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classificationDisposal->id,
        'facility_id' => $facility->id,
        'is_active' => true,
    ]);

    $collectionDate = Carbon::parse('2026-06-15');

    $orderRecycling = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    $orderDisposal = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $orderRecycling->id,
        'material_id' => $materialRecycling->id,
        'nett_weight' => 100,
    ]);

    OrderWasteStream::create([
        'order_id' => $orderDisposal->id,
        'material_id' => $materialDisposal->id,
        'nett_weight' => 25,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard', [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'from_date' => '2026-06-01',
        'to_date' => '2026-06-30',
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('dashboardData.classificationTotals.diverted.total', 100)
        ->where('dashboardData.classificationTotals.diverted.percentage', 80)
        ->where('dashboardData.classificationTotals.recycling.total', 100)
        ->where('dashboardData.classificationTotals.disposal.total', 25)
    );
});
