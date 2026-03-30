<?php

use App\Models\Branch;
use App\Models\ClientMonthlyMaterialSummary;
use App\Models\Company;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
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

function createPaperMaterialWithSetup(): array
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Grade Test Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'Provider', 'is_active' => true]);

    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Paper'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'Mixed Paper'], ['is_active' => true]);
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility GT'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
    ]);

    return [$user, $company, $branch, $site, $serviceProvider, $material, $wasteStream];
}

it('grades month column equals sum of drill-down row totals from order waste streams', function () {
    [$user, $company, $branch, $site, $serviceProvider, $material] = createPaperMaterialWithSetup();

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

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'nett_weight' => 100.25,
    ]);

    $service = app(OrderWasteStreamReportingService::class);
    $yearRows = $service->gradeSummaryForYear($company, $branch, $site, 2026);
    $paper = collect($yearRows)->firstWhere('name', 'Paper');

    expect($paper)->not->toBeNull()
        ->and((float) $paper['jun'])->toBe(100.25);

    $detail = $service->gradeMonthDailyDetail($company, $branch, $site, 'Paper', 6, 2026);
    $sumMaterialTotals = collect($detail['rows'])->sum(fn ($r) => (float) $r['total']);

    expect($sumMaterialTotals)->toBe(100.25);
});

it('ignores inflated client_monthly_material_summaries for grade summary', function () {
    [$user, $company, $branch, $site, $serviceProvider, $material] = createPaperMaterialWithSetup();

    $collectionDate = Carbon::parse('2026-07-10');
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

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'nett_weight' => 10,
    ]);

    ClientMonthlyMaterialSummary::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'year' => 2026,
        'month' => 7,
        'material_id' => $material->id,
        'waste_stream_id' => null,
        'total_weight' => 999999,
        'order_count' => 1,
        'last_updated_at' => now(),
    ]);

    $service = app(OrderWasteStreamReportingService::class);
    $yearRows = $service->gradeSummaryForYear($company, $branch, $site, 2026);
    $paper = collect($yearRows)->firstWhere('name', 'Paper');

    expect((float) $paper['jul'])->toBe(10.0);
});

it('dashboard grade-month-detail json matches service', function () {
    [$user, $company, $branch, $site, $serviceProvider, $material] = createPaperMaterialWithSetup();

    $collectionDate = Carbon::parse('2026-08-20');
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

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'nett_weight' => 42,
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.grade-month-detail', [
        'waste_stream' => 'Paper',
        'month' => 8,
        'year' => 2026,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
    ]));

    $response->assertSuccessful();
    expect((float) $response->json('rows.0.total'))->toBe(42.0)
        ->and((float) $response->json('rows.0.day20'))->toBe(42.0);
});
