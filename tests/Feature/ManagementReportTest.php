<?php

use App\Models\Branch;
use App\Models\Classification;
use App\Models\Company;
use App\Models\ContainerOption;
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

it('shows total waste diverted percentage and container totals per client for the month', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-06 12:00:00'));

    $admin = User::factory()->create();
    $admin->assignRole('manager');

    $company = Company::create(['name' => 'Mgmt Report Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = ServiceProvider::create(['name' => 'Mgmt SP', 'is_active' => true]);

    $wasteStream = WasteStream::firstOrCreate(['name' => 'Mgmt Stream'], ['is_active' => true]);
    $grade = Grade::firstOrCreate(['name' => 'Mgmt Grade'], ['is_active' => true]);
    $classification = Classification::firstOrCreate(['name' => 'Mgmt Recycling'], ['slug' => 'recycling', 'is_active' => true]);
    $facility = Facility::firstOrCreate(['name' => 'Mgmt Facility'], ['facility_type' => 'recycling', 'is_active' => true]);

    $material = Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'is_active' => true,
    ]);

    $containerOption = ContainerOption::create([
        'order_type' => 'recycling',
        'name' => 'Skip',
        'slug' => 'skip',
        'is_active' => true,
        'default_weight' => 0,
        'show_in_summary' => true,
    ]);

    $order = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $admin->id,
        'order_type' => 'recycling',
        'status' => 'finalized',
        'requested_collection_date' => Carbon::parse('2026-04-10'),
        'actual_collection_date' => Carbon::parse('2026-04-10'),
        'quantity_lines' => [
            ['container_option_id' => $containerOption->id, 'container_option_name' => 'Skip', 'quantity' => 3, 'description' => null],
        ],
    ]);

    OrderWasteStream::create(['order_id' => $order->id, 'material_id' => $material->id, 'gross_weight' => 100, 'nett_weight' => 100]);

    $response = $this->actingAs($admin)->get(route('reports.management-report', ['month' => 4, 'year' => 2026]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/ManagementReport')
        ->where('month', 4)
        ->where('year', 2026)
        ->has('rows', 1)
        ->where('rows.0.company_name', 'Mgmt Report Co')
        ->where('rows.0.total_waste_diverted_percentage', fn ($v) => abs((float) $v - 100.0) < 0.0001)
        ->where('rows.0.total_waste_managed_kg', fn ($v) => abs((float) $v - 100.0) < 0.0001)
        ->has('rows.0.container_totals', 1)
        ->where('rows.0.container_totals.0.name', 'Skip')
        ->where('rows.0.container_totals.0.quantity', 3)
    );

    Carbon::setTestNow();
});

it('forbids users without view-reports-all from the management report', function () {
    $company = Company::create(['name' => 'Scoped Co', 'is_active' => true]);
    $client = User::factory()->create(['company_id' => $company->id]);
    $client->assignRole('client');

    $this->actingAs($client)->get(route('reports.management-report'))->assertForbidden();
    $this->actingAs($client)->get(route('reports.management-report.export'))->assertForbidden();
    $this->actingAs($client)->get(route('reports.management-report.export-pdf'))->assertForbidden();
});

it('exports the management report as csv', function () {
    $admin = User::factory()->create();
    $admin->assignRole('manager');

    $response = $this->actingAs($admin)->get(route('reports.management-report.export', ['month' => 4, 'year' => 2026]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $response->assertHeader('content-disposition', 'attachment; filename=management_report_2026-04.csv');
});

it('exports the management report as pdf', function () {
    $admin = User::factory()->create();
    $admin->assignRole('manager');

    $response = $this->actingAs($admin)->get(route('reports.management-report.export-pdf', ['month' => 4, 'year' => 2026]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('rejects an invalid month', function () {
    $admin = User::factory()->create();
    $admin->assignRole('manager');

    $this->actingAs($admin)->get(route('reports.management-report', ['month' => 13]))
        ->assertSessionHasErrors('month');
});
