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
use App\Services\OrderWasteStreamReportingService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeClassificationTrendMaterial(string $classificationName, string $classificationSlug): Material
{
    $wasteStream = WasteStream::firstOrCreate(['name' => 'Trend Stream'], ['is_active' => true]);
    $grade = Grade::firstOrCreate(['name' => 'Trend Grade'], ['is_active' => true]);
    $classification = Classification::firstOrCreate(
        ['name' => $classificationName],
        ['slug' => $classificationSlug, 'is_active' => true]
    );
    $facility = Facility::firstOrCreate(['name' => 'Trend Facility'], ['facility_type' => 'recycling', 'is_active' => true]);

    return Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
    ]);
}

it('groups diverted, landfill, and managed totals by calendar month for the year', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Trend Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = ServiceProvider::create(['name' => 'Trend Provider', 'is_active' => true]);

    $recyclingMaterial = makeClassificationTrendMaterial('Trend Recycling', 'recycling');
    $disposalMaterial = makeClassificationTrendMaterial('Trend Disposal', 'disposed');

    $marchOrder = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-03-10'),
        'actual_collection_date' => Carbon::parse('2026-03-10'),
    ]);

    $juneOrder = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-06-05'),
        'actual_collection_date' => Carbon::parse('2026-06-05'),
    ]);

    OrderWasteStream::create(['order_id' => $marchOrder->id, 'material_id' => $recyclingMaterial->id, 'gross_weight' => 100, 'nett_weight' => 100]);
    OrderWasteStream::create(['order_id' => $marchOrder->id, 'material_id' => $disposalMaterial->id, 'gross_weight' => 25, 'nett_weight' => 25]);
    OrderWasteStream::create(['order_id' => $juneOrder->id, 'material_id' => $recyclingMaterial->id, 'gross_weight' => 40, 'nett_weight' => 40]);

    $service = app(OrderWasteStreamReportingService::class);
    $rows = $service->classificationTotalsByMonthForYear($company, $branch, $site, 2026);

    $diverted = collect($rows)->firstWhere('name', 'Total Waste Diverted');
    $landfill = collect($rows)->firstWhere('name', 'Waste to Landfill');
    $managed = collect($rows)->firstWhere('name', 'Total Waste Managed');

    expect($diverted['mar'])->toBe(100.0)
        ->and($diverted['jun'])->toBe(40.0)
        ->and($diverted['jan'])->toBe(0.0)
        ->and($diverted['total'])->toBe(140.0);

    expect($landfill['mar'])->toBe(25.0)
        ->and($landfill['jun'])->toBe(0.0)
        ->and($landfill['total'])->toBe(25.0);

    expect($managed['mar'])->toBe(125.0)
        ->and($managed['jun'])->toBe(40.0)
        ->and($managed['total'])->toBe(165.0);
});

it('returns zeroed rows for a year with no finalized orders', function () {
    $company = Company::create(['name' => 'Empty Trend Co', 'is_active' => true]);

    $service = app(OrderWasteStreamReportingService::class);
    $rows = $service->classificationTotalsByMonthForYear($company, null, null, 2026);

    expect($rows)->toHaveCount(3);
    foreach ($rows as $row) {
        expect($row['total'])->toBe(0.0);
        foreach (['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'] as $m) {
            expect($row[$m])->toBe(0.0);
        }
    }
});
