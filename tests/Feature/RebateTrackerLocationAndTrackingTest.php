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
        'gross_weight' => 100,
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

it('aggregates same-day same-grade streams and lists distinct order tracking numbers', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Agg Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'CBD', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'SP Agg', 'is_active' => true]);

    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility Agg'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Paper'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'CMW'], ['is_active' => true]);
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
        'rebate_rate' => 1,
        'client_rebate_share' => 100,
    ]);

    $collectionDate = Carbon::parse('2026-03-06');

    $orderA = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => null,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2603-30245',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    $orderB = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => null,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2603-30284',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $orderA->id,
        'material_id' => $material->id,
        'gross_weight' => 40,
        'nett_weight' => 40,
        'rebate_rate' => 1,
    ]);

    OrderWasteStream::create([
        'order_id' => $orderB->id,
        'material_id' => $material->id,
        'gross_weight' => 8,
        'nett_weight' => 8,
        'rebate_rate' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-31',
        'company_id' => $company->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/RebateTracker')
        ->has('rebateData', 1)
        ->where('rebateData.0.company_name', 'Agg Co')
        ->where('rebateData.0.grade', 'CMW')
        ->where('rebateData.0.weight', 48)
        ->where('rebateData.0.tracking_numbers', fn (string $v) => str_contains($v, 'RO-2603-30245') && str_contains($v, 'RO-2603-30284'))
    );
});

it('includes organic waste when material has rebate_offered false', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Organic Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'SP Organic', 'is_active' => true]);

    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Compost Facility'],
        ['facility_type' => 'compost', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recovered'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Organic Waste'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'Organics Recovered'], ['is_active' => true]);
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => false,
        'rebate_rate' => null,
        'client_rebate_share' => null,
    ]);

    $collectionDate = Carbon::parse('2026-03-12');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => null,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2603-ORG01',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'gross_weight' => 25,
        'nett_weight' => 25,
        'rebate_rate' => null,
    ]);

    $response = $this->actingAs($user)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-31',
        'company_id' => $company->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/RebateTracker')
        ->has('rebateData', 1)
        ->where('rebateData.0.company_name', 'Organic Co')
        ->where('rebateData.0.grade', 'Organics Recovered')
        ->where('rebateData.0.weight', 25)
        ->where('rebateData.0.tracking_numbers', 'RO-2603-ORG01')
    );
});

it('returns rebate tracker rows as a list after sorting', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $alphaCompany = Company::create(['name' => 'Alpha Co', 'is_active' => true]);
    $zuluCompany = Company::create(['name' => 'Zulu Co', 'is_active' => true]);

    $alphaBranch = Branch::create(['company_id' => $alphaCompany->id, 'name' => 'Alpha Branch', 'is_active' => true]);
    $zuluBranch = Branch::create(['company_id' => $zuluCompany->id, 'name' => 'Zulu Branch', 'is_active' => true]);

    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'SP Sort', 'is_active' => true]);
    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility Sort'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Plastic'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'PET'], ['is_active' => true]);
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
        'rebate_rate' => 1.5,
        'client_rebate_share' => 100,
    ]);

    $collectionDate = Carbon::parse('2026-03-15');

    // Intentionally create Zulu first so sorting changes key order.
    $zuluOrder = Order::create([
        'company_id' => $zuluCompany->id,
        'branch_id' => $zuluBranch->id,
        'site_id' => null,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2603-ZULU1',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    $alphaOrder = Order::create([
        'company_id' => $alphaCompany->id,
        'branch_id' => $alphaBranch->id,
        'site_id' => null,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2603-ALPHA1',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $zuluOrder->id,
        'material_id' => $material->id,
        'gross_weight' => 10,
        'nett_weight' => 10,
        'rebate_rate' => 1.5,
    ]);

    OrderWasteStream::create([
        'order_id' => $alphaOrder->id,
        'material_id' => $material->id,
        'gross_weight' => 12,
        'nett_weight' => 12,
        'rebate_rate' => 1.5,
    ]);

    $response = $this->actingAs($user)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-31',
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/RebateTracker')
        ->where('rebateData', fn ($rows) => str_starts_with((string) json_encode($rows), '['))
        ->where('rebateData.0.company_name', 'Alpha Co')
        ->where('rebateData.1.company_name', 'Zulu Co')
    );
});

it('shows admin users 100 percent rebate values in rebate tracker', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create([
        'name' => 'Rate Co Admin',
        'is_active' => true,
        'rebate_percentage' => 40,
    ]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site 1', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'SP Admin Rate', 'is_active' => true]);

    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility Admin Rate'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Glass'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'GLS'], ['is_active' => true]);
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
        'rebate_rate' => 2.5,
        'client_rebate_share' => 60,
    ]);

    $collectionDate = Carbon::parse('2026-04-03');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2604-ADMIN1',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
        'company_rebate_percentage' => 40,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'gross_weight' => 10,
        'nett_weight' => 10,
        'rebate_rate' => 2.5,
    ]);

    $response = $this->actingAs($user)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-30',
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/RebateTracker')
        ->where('rebateData.0.rate', fn ($value) => abs((float) $value - 2.5) < 0.0001)
        ->where('rebateData.0.total', fn ($value) => abs((float) $value - 25.0) < 0.0001)
    );
});

it('shows company users configured rebate share values in rebate tracker', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $companyUser = User::factory()->create();
    $companyUser->assignRole('company_user');

    $company = Company::create([
        'name' => 'Rate Co Client',
        'is_active' => true,
        'rebate_percentage' => null,
    ]);
    $companyUser->companies()->attach($company->id, ['role' => 'viewer']);

    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site 1', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'SP Client Rate', 'is_active' => true]);

    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility Client Rate'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'PET Stream'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'PET-1'], ['is_active' => true]);
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
        'rebate_rate' => 3.0,
        'client_rebate_share' => 60,
    ]);

    $collectionDate = Carbon::parse('2026-04-04');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $admin->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2604-CLIENT1',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
        'company_rebate_percentage' => null,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'gross_weight' => 10,
        'nett_weight' => 10,
        'rebate_rate' => 3.0,
    ]);

    $response = $this->actingAs($companyUser)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-30',
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/RebateTracker')
        ->where('rebateData.0.rate', fn ($value) => abs((float) $value - 1.8) < 0.0001)
        ->where('rebateData.0.total', fn ($value) => abs((float) $value - 18.0) < 0.0001)
    );
});

it('allows client users with direct company assignment to access rebate tracker', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $company = Company::create(['name' => 'Direct Company', 'is_active' => true]);
    $client->update(['company_id' => $company->id]);

    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site 1', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'SP Direct', 'is_active' => true]);

    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility Direct Company'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Paper Direct'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'HL-D'], ['is_active' => true]);
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
        'rebate_rate' => 1.2,
        'client_rebate_share' => 100,
    ]);

    $collectionDate = Carbon::parse('2026-04-15');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $client->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2604-DIRECT1',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);

    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'gross_weight' => 10,
        'nett_weight' => 10,
        'rebate_rate' => 1.2,
    ]);

    $response = $this->actingAs($client)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-30',
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/RebateTracker')
        ->has('rebateData', 1)
        ->where('rebateData.0.company_name', 'Direct Company')
    );
});

it('shows only assigned company in rebate tracker company dropdown for company users', function () {
    $companyUser = User::factory()->create();
    $companyUser->assignRole('company_user');

    $assignedCompany = Company::create(['name' => 'Assigned Co', 'is_active' => true]);
    $otherCompany = Company::create(['name' => 'Other Co', 'is_active' => true]);
    $companyUser->companies()->attach($assignedCompany->id, ['role' => 'viewer']);

    // Ensure there is data available for the assigned company.
    $branch = Branch::create(['company_id' => $assignedCompany->id, 'name' => 'Main', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site 1', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'SP Dropdown', 'is_active' => true]);
    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility Dropdown'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Paper Dropdown'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'HL-DD'], ['is_active' => true]);
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
        'rebate_rate' => 1.0,
        'client_rebate_share' => 100,
    ]);
    $collectionDate = Carbon::parse('2026-04-20');
    $order = Order::create([
        'company_id' => $assignedCompany->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $companyUser->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2604-DROP01',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);
    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'gross_weight' => 10,
        'nett_weight' => 10,
        'rebate_rate' => 1.0,
    ]);

    $response = $this->actingAs($companyUser)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-30',
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/RebateTracker')
        ->has('companies', 1)
        ->where('companies.0.name', 'Assigned Co')
        ->where('companies.0.id', $assignedCompany->id)
    );

    expect($otherCompany->id)->not->toBe($assignedCompany->id);
});

it('includes site-less orders for company users when company matches scope', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $companyUser = User::factory()->create();
    $companyUser->assignRole('company_user');

    $company = Company::create(['name' => 'Scoped Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Main', 'is_active' => true]);
    $companyUser->companies()->attach($company->id, ['role' => 'viewer']);

    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'SP Scoped', 'is_active' => true]);
    $facility = \App\Models\Facility::firstOrCreate(
        ['name' => 'Facility Scoped'],
        ['facility_type' => 'recycling', 'is_active' => true]
    );
    $classification = \App\Models\Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);
    $wasteStream = \App\Models\WasteStream::firstOrCreate(['name' => 'Paper Scoped'], ['is_active' => true]);
    $grade = \App\Models\Grade::firstOrCreate(['name' => 'HL-S'], ['is_active' => true]);
    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
        'rebate_offered' => true,
        'rebate_rate' => 2.0,
        'client_rebate_share' => 100,
    ]);

    $collectionDate = Carbon::parse('2026-04-22');
    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => null,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $admin->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'tracking_number' => 'RO-2604-SCOPE1',
        'requested_collection_date' => $collectionDate,
        'actual_collection_date' => $collectionDate,
    ]);
    OrderWasteStream::create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'gross_weight' => 5,
        'nett_weight' => 5,
        'rebate_rate' => 2.0,
    ]);

    $response = $this->actingAs($companyUser)->get(route('reports.rebate-tracker', [
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-30',
        'company_id' => $company->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/RebateTracker')
        ->has('rebateData', 1)
        ->where('rebateData.0.company_name', 'Scoped Co')
        ->where('rebateData.0.site_name', '—')
    );
});
