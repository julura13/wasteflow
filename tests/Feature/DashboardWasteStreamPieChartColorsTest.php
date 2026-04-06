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
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('assigns client palette hex colors to dashboard waste stream pie segments', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Pie Color Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'Provider PC', 'is_active' => true]);

    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility PC'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);

    $streams = [
        'Paper' => ['grade' => 'G Paper', 'color' => '#2563eb'],
        'Plastic' => ['grade' => 'G Plastic', 'color' => '#eab308'],
        'Metal' => ['grade' => 'G Metal', 'color' => '#6b7280'],
        'Waste' => ['grade' => 'G Waste', 'color' => '#171717'],
    ];

    $materials = [];
    foreach ($streams as $streamName => $meta) {
        $ws = \App\Models\WasteStream::firstOrCreate(['name' => $streamName], ['is_active' => true]);
        $grade = \App\Models\Grade::firstOrCreate(['name' => $meta['grade']], ['is_active' => true]);
        $materials[$streamName] = Material::create([
            'waste_stream_id' => $ws->id,
            'grade_id' => $grade->id,
            'classification_id' => $classification->id,
            'facility_id' => $facility->id,
            'is_active' => true,
        ]);
    }

    $collectionDate = Carbon::parse('2026-06-15');
    $order = Order::create([
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

    foreach ($materials as $material) {
        OrderWasteStream::create([
            'order_id' => $order->id,
            'material_id' => $material->id,
            'nett_weight' => 10,
        ]);
    }

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
        ->has('dashboardData.wasteStreamTotals', 4)
        ->where('dashboardData.wasteStreamTotals', function (Collection $totals) use ($streams) {
            $byName = $totals->keyBy('name');
            foreach ($streams as $name => $meta) {
                expect($byName->get($name)['color'])->toBe($meta['color']);
            }

            return true;
        })
    );
});
