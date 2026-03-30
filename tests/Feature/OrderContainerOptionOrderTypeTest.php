<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\ContainerOption;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('rejects creating a recycling order with a waste-only container option', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Br', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $provider = ServiceProvider::create([
        'name' => 'Provider',
        'types' => ['general'],
        'is_active' => true,
    ]);

    $wasteOpt = ContainerOption::create([
        'order_type' => 'waste',
        'name' => 'Waste Bin',
        'is_active' => true,
    ]);
    ContainerOption::create([
        'order_type' => 'recycling',
        'name' => 'Scrap Load',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('orders.store'), [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'order_type' => 'recycling',
        'requested_collection_date' => now()->addDay()->format('Y-m-d'),
        'quantity_lines' => [
            [
                'container_option_id' => $wasteOpt->id,
                'quantity' => 2,
            ],
        ],
    ]);

    $response->assertSessionHasErrors('quantity_lines.0.container_option_id');
});

it('creates a recycling order when container option matches order type', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Br', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $provider = ServiceProvider::create([
        'name' => 'Provider',
        'types' => ['general'],
        'is_active' => true,
    ]);

    $recyclingOpt = ContainerOption::create([
        'order_type' => 'recycling',
        'name' => 'Loose Bags',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('orders.store'), [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'order_type' => 'recycling',
        'requested_collection_date' => now()->addDay()->format('Y-m-d'),
        'quantity_lines' => [
            [
                'container_option_id' => $recyclingOpt->id,
                'quantity' => 3,
                'description' => 'Dock A',
            ],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('orders', [
        'order_type' => 'recycling',
        'estimated_quantity' => 3,
    ]);
});
