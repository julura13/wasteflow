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

it('renders the waste stream collection report page with grouped breakdown data', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Waste Stream Page Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main', 'is_active' => true]);
    $serviceProvider = ServiceProvider::create(['name' => 'SP Page', 'is_active' => true]);
    $facility = Facility::firstOrCreate(['name' => 'Facility Page'], ['facility_type' => 'recycling', 'is_active' => true]);
    $classification = Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = WasteStream::firstOrCreate(['name' => 'Plastic Page'], ['is_active' => true]);
    $grade = Grade::firstOrCreate(['name' => 'Film LD Clear Page'], ['is_active' => true]);

    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
    ]);

    $collectionDate = Carbon::parse('2026-03-10');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => null,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2603-PAGE01',
        'slip_number' => 'CL-PAGE01',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'gross_weight' => 20,
        'nett_weight' => 20,
    ]);

    $response = $this->actingAs($user)->get(route('reports.waste-stream-collection', [
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-31',
        'company_id' => $company->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/WasteStreamCollectionReport')
        ->has('wasteStreamBreakdown', 1)
        ->where('wasteStreamBreakdown.0.heading', 'Plastic Page - Film LD Clear Page')
        ->where('wasteStreamBreakdown.0.subtotal_weight', fn ($value) => abs((float) $value - 20.0) < 0.0001)
        ->where('wasteStreamBreakdown.0.rows.0.tracking_number', 'RO-2603-PAGE01')
        ->where('wasteStreamBreakdown.0.rows.0.slip_number', 'CL-PAGE01')
        ->where('totalWeight', fn ($value) => abs((float) $value - 20.0) < 0.0001)
    );
});
