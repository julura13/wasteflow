<?php

use App\Models\Branch;
use App\Models\Classification;
use App\Models\Company;
use App\Models\Facility;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Models\User;
use App\Models\WasteStream;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('renders the resource intelligence page with report data for a selected company', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::query()->create(['name' => 'Test Co', 'is_active' => true]);

    $response = $this->actingAs($user)->get(route('reports.resource-intelligence', [
        'company_id' => $company->id,
        'month' => 4,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/ResourceIntelligence')
        ->has('reportData')
        ->where('reportData.scopeDisplayName', 'Test Co')
        ->where('filters.company_id', $company->id)
    );
});

it('builds scope display name as company, branch and site when available', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::query()->create(['name' => 'Test Co', 'is_active' => true]);
    $branch = Branch::query()->create([
        'company_id' => $company->id,
        'name' => 'Cape Town',
        'is_active' => true,
    ]);
    $site = Site::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Waterfront',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('reports.resource-intelligence', [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'month' => 4,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/ResourceIntelligence')
        ->where('reportData.scopeDisplayName', 'Test Co - Cape Town - Waterfront')
    );
});

it('includes jan-dec grade and waste-management trend series plus new energy-derived impact metrics', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::create(['name' => 'Trend Report Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = ServiceProvider::create(['name' => 'Trend Report SP', 'is_active' => true]);

    $wasteStream = WasteStream::firstOrCreate(['name' => 'Paper'], ['is_active' => true]);
    $grade = Grade::firstOrCreate(['name' => 'Trend Report Grade'], ['is_active' => true]);
    $classification = Classification::firstOrCreate(['name' => 'Trend Report Recycling'], ['slug' => 'recycling', 'is_active' => true]);
    $facility = Facility::firstOrCreate(['name' => 'Trend Report Facility'], ['facility_type' => 'recycling', 'is_active' => true]);

    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
    ]);

    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-10'),
        'actual_collection_date' => Carbon::parse('2026-04-10'),
    ]);

    OrderWasteStream::create(['order_id' => $order->id, 'material_id' => $material->id, 'gross_weight' => 100, 'nett_weight' => 100]);

    $response = $this->actingAs($user)->get(route('reports.resource-intelligence', [
        'company_id' => $company->id,
        'month' => 4,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/ResourceIntelligence')
        ->has('reportData.gradeSummaryByYear')
        ->has('reportData.wasteManagementTrendByYear', 3)
        ->where('reportData.gradeSummaryByYear.0.apr', fn ($v) => abs((float) $v - 100.0) < 0.0001)
        ->where('reportData.wasteManagementTrendByYear.0.name', 'Total Waste Diverted')
        ->where('reportData.wasteManagementTrendByYear.0.apr', fn ($v) => abs((float) $v - 100.0) < 0.0001)
        ->has('reportData.environmentalImpact.barrelsOfOilSaved')
        ->has('reportData.environmentalImpact.homesPoweredOneMonth')
    );
});
