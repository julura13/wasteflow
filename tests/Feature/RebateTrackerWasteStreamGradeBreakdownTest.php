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
use App\Services\RebateTrackerReportService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeRebateTrackerScope(): array
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Breakdown Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = ServiceProvider::create(['name' => 'Provider', 'is_active' => true]);
    $facility = Facility::firstOrCreate(['name' => 'Facility BC'], ['facility_type' => 'recycling', 'is_active' => true]);
    $classification = Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);

    return [$user, $company, $branch, $site, $serviceProvider, $facility, $classification];
}

function makeMaterial(string $wasteStreamName, string $gradeName, Facility $facility, Classification $classification): Material
{
    $wasteStream = WasteStream::firstOrCreate(['name' => $wasteStreamName], ['is_active' => true]);
    $grade = Grade::firstOrCreate(['name' => $gradeName], ['is_active' => true]);

    return Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
    ]);
}

it('groups breakdown rows by waste stream and grade heading with a weight subtotal', function () {
    [$user, $company, $branch, $site, $serviceProvider, $facility, $classification] = makeRebateTrackerScope();

    $plasticClear = makeMaterial('Plastic', 'Film LD Clear', $facility, $classification);
    $plasticColour = makeMaterial('Plastic', 'Film LD Colour', $facility, $classification);

    $orderOne = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-02-02'),
        'actual_collection_date' => Carbon::parse('2026-02-02'),
        'tracking_number' => 'RO-2602-00001',
        'slip_number' => '3422907',
        'quantity_lines' => [
            ['container_option_id' => null, 'container_option_name' => 'Bale', 'quantity' => 1, 'description' => null],
        ],
    ]);

    $orderTwo = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-02-12'),
        'actual_collection_date' => Carbon::parse('2026-02-12'),
        'tracking_number' => 'RO-2602-00002',
        'slip_number' => '3422918',
        'quantity_lines' => [
            ['container_option_id' => null, 'container_option_name' => 'Bale', 'quantity' => 1, 'description' => null],
        ],
    ]);

    OrderWasteStream::create(['order_id' => $orderOne->id, 'material_id' => $plasticClear->id, 'gross_weight' => 6.34, 'nett_weight' => 6.34]);
    OrderWasteStream::create(['order_id' => $orderTwo->id, 'material_id' => $plasticClear->id, 'gross_weight' => 6.33, 'nett_weight' => 6.33]);
    OrderWasteStream::create(['order_id' => $orderOne->id, 'material_id' => $plasticColour->id, 'gross_weight' => 12.67, 'nett_weight' => 12.67]);

    $service = app(RebateTrackerReportService::class);
    $breakdown = $service->getWasteStreamGradeBreakdown(
        '2026-02-01',
        '2026-02-28',
        $company->id,
        null,
        null,
        $user,
        [],
    );

    expect($breakdown)->toHaveCount(2);

    $clearGroup = $breakdown->firstWhere('heading', 'Plastic - Film LD Clear');
    expect($clearGroup)->not->toBeNull()
        ->and($clearGroup['rows'])->toHaveCount(2)
        ->and((float) $clearGroup['subtotal_weight'])->toBe(12.67);

    $trackingNumbers = collect($clearGroup['rows'])->pluck('tracking_number')->all();
    expect($trackingNumbers)->toContain('RO-2602-00001', 'RO-2602-00002');

    $slipNumbers = collect($clearGroup['rows'])->pluck('slip_number')->all();
    expect($slipNumbers)->toContain('3422907', '3422918');

    $firstRow = collect($clearGroup['rows'])->firstWhere('tracking_number', 'RO-2602-00001');
    expect($firstRow['quantity'])->toContain('Bale');

    $colourGroup = $breakdown->firstWhere('heading', 'Plastic - Film LD Colour');
    expect($colourGroup)->not->toBeNull()
        ->and((float) $colourGroup['subtotal_weight'])->toBe(12.67)
        ->and($colourGroup['rows'])->toHaveCount(1);
});

it('falls back to Uncategorized and Ungraded headings when a stream has no material', function () {
    [$user, $company, $branch, $site, $serviceProvider] = makeRebateTrackerScope();

    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-02-05'),
        'actual_collection_date' => Carbon::parse('2026-02-05'),
        'tracking_number' => 'RO-2602-00003',
        'slip_number' => null,
    ]);

    // material_id is null (e.g. the material was deleted after the fact, nullOnDelete),
    // so eligibility must be satisfied directly via a positive rebate_rate on the stream.
    OrderWasteStream::create(['order_id' => $order->id, 'material_id' => null, 'gross_weight' => 5, 'nett_weight' => 5, 'rebate_rate' => 1.5]);

    $service = app(RebateTrackerReportService::class);
    $breakdown = $service->getWasteStreamGradeBreakdown(
        '2026-02-01',
        '2026-02-28',
        $company->id,
        null,
        null,
        $user,
        [],
    );

    expect($breakdown)->toHaveCount(1);
    expect($breakdown->first()['heading'])->toBe('Uncategorized - Ungraded');
    expect($breakdown->first()['rows'][0]['slip_number'])->toBe('—');
    expect($breakdown->first()['rows'][0]['quantity'])->toBe('—');
});
