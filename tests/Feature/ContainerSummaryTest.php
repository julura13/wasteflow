<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Models\User;
use App\Services\OrderWasteStreamReportingService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('aggregates waste order quantity lines by container type and month', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Test Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $base = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'finalized',
    ];

    // January: 2× 240l Wheelie Bin - General Waste
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-01-10'),
        'actual_collection_date' => Carbon::parse('2026-01-10'),
        'quantity_lines' => [
            ['container_option_name' => '240l Wheelie Bin', 'description' => 'General Waste', 'quantity' => 2],
        ],
    ]));

    // January: 1× 6m3 Skip - General Waste
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-01-15'),
        'actual_collection_date' => Carbon::parse('2026-01-15'),
        'quantity_lines' => [
            ['container_option_name' => '6m3 Skip', 'description' => 'General Waste', 'quantity' => 1],
        ],
    ]));

    // February: 3× 240l Wheelie Bin - General Waste
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-02-05'),
        'actual_collection_date' => Carbon::parse('2026-02-05'),
        'quantity_lines' => [
            ['container_option_name' => '240l Wheelie Bin', 'description' => 'General Waste', 'quantity' => 3],
        ],
    ]));

    // Container with no description (no " - " suffix)
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-03-01'),
        'actual_collection_date' => Carbon::parse('2026-03-01'),
        'quantity_lines' => [
            ['container_option_name' => '23m3 Compactor', 'description' => '', 'quantity' => 1],
        ],
    ]));

    $service = app(OrderWasteStreamReportingService::class);
    $rows = $service->containerSummaryForYear($company, null, null, 2026);

    // Sorted alphabetically by name
    expect(array_column($rows, 'name'))->toBe([
        '23m3 Compactor',
        '240l Wheelie Bin - General Waste',
        '6m3 Skip - General Waste',
    ]);

    $wheelieBin = collect($rows)->firstWhere('name', '240l Wheelie Bin - General Waste');
    expect($wheelieBin['jan'])->toBe(2);
    expect($wheelieBin['feb'])->toBe(3);
    expect($wheelieBin['mar'])->toBe(0);
    expect($wheelieBin['total'])->toBe(5);

    $skip = collect($rows)->firstWhere('name', '6m3 Skip - General Waste');
    expect($skip['jan'])->toBe(1);
    expect($skip['total'])->toBe(1);

    $compactor = collect($rows)->firstWhere('name', '23m3 Compactor');
    expect($compactor['mar'])->toBe(1);
    expect($compactor['total'])->toBe(1);
});

it('excludes recycling orders from container summary', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Recycle Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $base = [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-10'),
        'actual_collection_date' => Carbon::parse('2026-04-10'),
    ];

    Order::create(array_merge($base, [
        'order_type' => 'recycling',
        'quantity_lines' => [
            ['container_option_name' => 'Scrap Load', 'description' => '', 'quantity' => 5],
        ],
    ]));

    Order::create(array_merge($base, [
        'order_type' => 'waste',
        'quantity_lines' => [
            ['container_option_name' => '240l Wheelie Bin', 'description' => 'General Waste', 'quantity' => 2],
        ],
    ]));

    $service = app(OrderWasteStreamReportingService::class);
    $rows = $service->containerSummaryForYear($company, null, null, 2026);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['name'])->toBe('240l Wheelie Bin - General Waste');
});

it('returns daily counts for a container type via the daily detail endpoint', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Daily Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $base = [
        'company_id' => $company->id, 'branch_id' => $branch->id, 'site_id' => $site->id,
        'service_provider_id' => $provider->id, 'created_by' => $user->id,
        'order_type' => 'waste', 'status' => 'finalized',
    ];

    // Day 5: 3 bins; day 10: 2 bins
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-03-05'),
        'actual_collection_date' => Carbon::parse('2026-03-05'),
        'quantity_lines' => [['container_option_name' => '240l Wheelie Bin', 'description' => 'General Waste', 'quantity' => 3]],
    ]));
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-03-10'),
        'actual_collection_date' => Carbon::parse('2026-03-10'),
        'quantity_lines' => [['container_option_name' => '240l Wheelie Bin', 'description' => 'General Waste', 'quantity' => 2]],
    ]));

    $response = $this->actingAs($user)->getJson(route('dashboard.container-month-detail', [
        'container_option_name' => '240l Wheelie Bin',
        'description' => 'General Waste',
        'month' => 3,
        'year' => 2026,
        'company_id' => $company->id,
    ]));

    $response->assertOk();
    $data = $response->json();
    expect($data['label'])->toBe('240l Wheelie Bin - General Waste');
    expect($data['days_in_month'])->toBe(31);
    expect($data['counts'][5])->toBe(3);
    expect($data['counts'][10])->toBe(2);
    expect($data['counts'][1])->toBe(0);
});

it('returns orders for a day filtered by container type', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Orders Day Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $base = [
        'company_id' => $company->id, 'branch_id' => $branch->id, 'site_id' => $site->id,
        'service_provider_id' => $provider->id, 'created_by' => $user->id,
        'order_type' => 'waste', 'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-07'),
        'actual_collection_date' => Carbon::parse('2026-04-07'),
    ];

    Order::create(array_merge($base, [
        'tracking_number' => 'WO-MATCH',
        'quantity_lines' => [['container_option_name' => '240l Wheelie Bin', 'description' => 'General Waste', 'quantity' => 2]],
    ]));
    Order::create(array_merge($base, [
        'tracking_number' => 'WO-OTHER',
        'quantity_lines' => [['container_option_name' => '6m3 Skip', 'description' => 'General Waste', 'quantity' => 1]],
    ]));

    $response = $this->actingAs($user)->getJson(route('dashboard.orders-for-day-by-container', [
        'date' => '2026-04-07',
        'container_option_name' => '240l Wheelie Bin',
        'description' => 'General Waste',
        'company_id' => $company->id,
    ]));

    $response->assertOk();
    $orders = $response->json('orders');
    expect($orders)->toHaveCount(1);
    expect($orders[0]['tracking_number'])->toBe('WO-MATCH');
    expect($orders[0]['quantity'])->toBe(2);
});

it('returns container summary via dashboard controller', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Dashboard Co', 'is_active' => true]);
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
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-05-20'),
        'actual_collection_date' => Carbon::parse('2026-05-20'),
        'quantity_lines' => [
            ['container_option_name' => '240l Wheelie Bin', 'description' => 'General Waste', 'quantity' => 4],
        ],
    ]);

    $response = $this->actingAs($user)->get(route('dashboard', [
        'company_id' => $company->id,
        'from_date' => '2026-05-01',
        'to_date' => '2026-05-31',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Dashboard')
        ->has('containerSummaryByYear', 1)
        ->where('containerSummaryByYear.0.name', '240l Wheelie Bin - General Waste')
        ->where('containerSummaryByYear.0.may', 4)
        ->where('containerSummaryByYear.0.total', 4));
});
