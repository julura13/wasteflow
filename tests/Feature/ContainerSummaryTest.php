<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\ContainerOption;
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

// Helper: create a ContainerOption with show_in_summary flag
function makeContainerOption(string $name, string $orderType = 'waste', bool $showInSummary = true): ContainerOption
{
    return ContainerOption::create([
        'name' => $name,
        'order_type' => $orderType,
        'is_active' => true,
        'show_in_summary' => $showInSummary,
    ]);
}

it('aggregates quantity lines for containers flagged show_in_summary by month', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Test Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $wheelie = makeContainerOption('240l Wheelie Bin', 'waste', showInSummary: true);
    $skip = makeContainerOption('6m3 Skip', 'waste', showInSummary: true);
    $compactor = makeContainerOption('23m3 Compactor', 'waste', showInSummary: true);

    $base = [
        'company_id' => $company->id, 'branch_id' => $branch->id, 'site_id' => $site->id,
        'service_provider_id' => $provider->id, 'created_by' => $user->id,
        'order_type' => 'waste', 'status' => 'finalized',
    ];

    // January: 2× 240l Wheelie Bin - General Waste
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-01-10'),
        'actual_collection_date' => Carbon::parse('2026-01-10'),
        'quantity_lines' => [
            ['container_option_id' => $wheelie->id, 'container_option_name' => $wheelie->name, 'description' => 'General Waste', 'quantity' => 2],
        ],
    ]));

    // January: 1× 6m3 Skip - General Waste
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-01-15'),
        'actual_collection_date' => Carbon::parse('2026-01-15'),
        'quantity_lines' => [
            ['container_option_id' => $skip->id, 'container_option_name' => $skip->name, 'description' => 'General Waste', 'quantity' => 1],
        ],
    ]));

    // February: 3× 240l Wheelie Bin - General Waste
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-02-05'),
        'actual_collection_date' => Carbon::parse('2026-02-05'),
        'quantity_lines' => [
            ['container_option_id' => $wheelie->id, 'container_option_name' => $wheelie->name, 'description' => 'General Waste', 'quantity' => 3],
        ],
    ]));

    // March: 1× 23m3 Compactor (no description)
    Order::create(array_merge($base, [
        'requested_collection_date' => Carbon::parse('2026-03-01'),
        'actual_collection_date' => Carbon::parse('2026-03-01'),
        'quantity_lines' => [
            ['container_option_id' => $compactor->id, 'container_option_name' => $compactor->name, 'description' => '', 'quantity' => 1],
        ],
    ]));

    $rows = app(OrderWasteStreamReportingService::class)->containerSummaryForYear($company, null, null, 2026);

    expect(array_column($rows, 'name'))->toBe([
        '23m3 Compactor',
        '240l Wheelie Bin - General Waste',
        '6m3 Skip - General Waste',
    ]);

    $wb = collect($rows)->firstWhere('name', '240l Wheelie Bin - General Waste');
    expect($wb['jan'])->toBe(2);
    expect($wb['feb'])->toBe(3);
    expect($wb['mar'])->toBe(0);
    expect($wb['total'])->toBe(5);

    expect(collect($rows)->firstWhere('name', '6m3 Skip - General Waste')['total'])->toBe(1);
    expect(collect($rows)->firstWhere('name', '23m3 Compactor')['mar'])->toBe(1);
});

it('excludes containers not flagged with show_in_summary', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Flag Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $visible = makeContainerOption('240l Wheelie Bin', 'waste', showInSummary: true);
    $hidden = makeContainerOption('23m3 Compactor', 'waste', showInSummary: false);

    $base = [
        'company_id' => $company->id, 'branch_id' => $branch->id, 'site_id' => $site->id,
        'service_provider_id' => $provider->id, 'created_by' => $user->id,
        'order_type' => 'waste', 'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-10'),
        'actual_collection_date' => Carbon::parse('2026-04-10'),
    ];

    Order::create(array_merge($base, ['quantity_lines' => [
        ['container_option_id' => $visible->id, 'container_option_name' => $visible->name, 'description' => 'General Waste', 'quantity' => 2],
    ]]));

    Order::create(array_merge($base, ['quantity_lines' => [
        ['container_option_id' => $hidden->id, 'container_option_name' => $hidden->name, 'description' => '', 'quantity' => 5],
    ]]));

    $rows = app(OrderWasteStreamReportingService::class)->containerSummaryForYear($company, null, null, 2026);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['name'])->toBe('240l Wheelie Bin - General Waste');
});

it('includes recycling order containers when flagged show_in_summary', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Recycle Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $scrap = makeContainerOption('Test Scrap Bin', 'recycling', showInSummary: true);
    $wheelie = makeContainerOption('Test Wheelie 240l', 'waste', showInSummary: true);

    $base = [
        'company_id' => $company->id, 'branch_id' => $branch->id, 'site_id' => $site->id,
        'service_provider_id' => $provider->id, 'created_by' => $user->id,
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-10'),
        'actual_collection_date' => Carbon::parse('2026-04-10'),
    ];

    Order::create(array_merge($base, ['order_type' => 'recycling', 'quantity_lines' => [
        ['container_option_id' => $scrap->id, 'container_option_name' => $scrap->name, 'description' => '', 'quantity' => 5],
    ]]));

    Order::create(array_merge($base, ['order_type' => 'waste', 'quantity_lines' => [
        ['container_option_id' => $wheelie->id, 'container_option_name' => $wheelie->name, 'description' => 'General Waste', 'quantity' => 2],
    ]]));

    $rows = app(OrderWasteStreamReportingService::class)->containerSummaryForYear($company, null, null, 2026);

    // Both containers show (recycling + waste), both flagged
    expect($rows)->toHaveCount(2);
    $names = array_column($rows, 'name');
    expect($names)->toContain('Test Scrap Bin');
    expect($names)->toContain('Test Wheelie 240l - General Waste');
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

it('uses actual_quantity instead of estimated line quantity for single-line orders', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Actual Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $bin = makeContainerOption('240l Wheelie Bin', 'waste', showInSummary: true);

    // Estimated 5, actual 3 — actual should win
    Order::create([
        'company_id' => $company->id, 'branch_id' => $branch->id,
        'service_provider_id' => $provider->id, 'created_by' => $user->id,
        'order_type' => 'waste', 'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-06-10'),
        'actual_collection_date' => Carbon::parse('2026-06-10'),
        'estimated_quantity' => 5,
        'actual_quantity' => 3,
        'quantity_lines' => [
            ['container_option_id' => $bin->id, 'container_option_name' => $bin->name, 'description' => '', 'quantity' => 5],
        ],
    ]);

    $rows = app(OrderWasteStreamReportingService::class)->containerSummaryForYear($company, null, null, 2026);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['jun'])->toBe(3);
    expect($rows[0]['total'])->toBe(3);
});

it('proportionally scales multi-line quantities when actual differs from estimated', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Scale Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $bags = makeContainerOption('Loose Bags', 'waste', showInSummary: true);
    $cage = makeContainerOption('8m³ Cage', 'waste', showInSummary: true);

    // Estimated: 16 bags + 4 cages = 20; actual = 10 (half)
    Order::create([
        'company_id' => $company->id, 'branch_id' => $branch->id,
        'service_provider_id' => $provider->id, 'created_by' => $user->id,
        'order_type' => 'waste', 'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-06-10'),
        'actual_collection_date' => Carbon::parse('2026-06-10'),
        'estimated_quantity' => 20,
        'actual_quantity' => 10,
        'quantity_lines' => [
            ['container_option_id' => $bags->id, 'container_option_name' => $bags->name, 'description' => '', 'quantity' => 16],
            ['container_option_id' => $cage->id, 'container_option_name' => $cage->name, 'description' => '', 'quantity' => 4],
        ],
    ]);

    $rows = app(OrderWasteStreamReportingService::class)->containerSummaryForYear($company, null, null, 2026);

    $bagsRow = collect($rows)->firstWhere('name', 'Loose Bags');
    $cageRow = collect($rows)->firstWhere('name', '8m³ Cage');

    // 16 * 10/20 = 8, 4 * 10/20 = 2
    expect($bagsRow['jun'])->toBe(8);
    expect($cageRow['jun'])->toBe(2);
});

it('uses actual_quantity in daily detail for single-line orders', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Daily Actual Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    Order::create([
        'company_id' => $company->id, 'branch_id' => $branch->id,
        'service_provider_id' => $provider->id, 'created_by' => $user->id,
        'order_type' => 'waste', 'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-06-05'),
        'actual_collection_date' => Carbon::parse('2026-06-05'),
        'estimated_quantity' => 10,
        'actual_quantity' => 7,
        'quantity_lines' => [
            ['container_option_name' => '240l Wheelie Bin', 'description' => 'General Waste', 'quantity' => 10],
        ],
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.container-month-detail', [
        'container_option_name' => '240l Wheelie Bin',
        'description' => 'General Waste',
        'month' => 6,
        'year' => 2026,
        'company_id' => $company->id,
    ]));

    $response->assertOk();
    expect($response->json('counts.5'))->toBe(7);
});

it('returns container summary via dashboard controller', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Dashboard Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    $wheelie = makeContainerOption('240l Wheelie Bin', 'waste', showInSummary: true);

    Order::create([
        'company_id' => $company->id, 'branch_id' => $branch->id, 'site_id' => $site->id,
        'service_provider_id' => $provider->id, 'created_by' => $user->id,
        'order_type' => 'waste', 'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-05-20'),
        'actual_collection_date' => Carbon::parse('2026-05-20'),
        'quantity_lines' => [
            ['container_option_id' => $wheelie->id, 'container_option_name' => $wheelie->name, 'description' => 'General Waste', 'quantity' => 4],
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
