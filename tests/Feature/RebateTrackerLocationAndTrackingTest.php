<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('resolves company and branch from order when site is absent and includes tracking numbers in rebate tracker rows', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Rebate Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main Branch', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'SP', 'is_active' => true]);

    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility RT'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Paper'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'HL 1'], ['is_active' => true]);
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
        'rebate_rate' => 2.5,
        'client_rebate_share' => 100,
    ]);

    $collectionDate = Carbon::parse('2026-03-30');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => null,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2603-99901',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'nett_weight' => 100,
        'rebate_rate' => 2.5,
    ]);

    $response = $this->actingAs($user)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-03-01',
        'end_date' => '2026-04-30',
        'company_id' => $company->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/RebateTracker')
        ->has('rebateData', 1)
        ->where('rebateData.0.company_name', 'Rebate Co')
        ->where('rebateData.0.branch_name', 'Main Branch')
        ->where('rebateData.0.site_name', '—')
        ->where('rebateData.0.tracking_numbers', 'RO-2603-99901')
        ->where('rebateData.0.grade', 'HL 1')
    );
});
