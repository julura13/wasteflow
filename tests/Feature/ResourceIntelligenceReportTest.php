<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('renders the resource intelligence page with report data for a selected company', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::query()->create(['name' => 'Test Co', 'is_active' => true]);

    $response = $this->actingAs($user)->get(route('reports.resource-intelligence', [
        'company_id' => $company->id,
        'month' => 4,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/ResourceIntelligence')
        ->has('reportData')
        ->where('reportData.scopeDisplayName', 'Test Co')
        ->where('filters.company_id', $company->id)
    );
});

it('builds scope display name as company, branch and site when available', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');

    $company = Company::query()->create(['name' => 'Test Co', 'is_active' => true]);
    $branch = Branch::query()->create([
        'company_id' => $company->id,
        'name' => 'Cape Town',
        'is_active' => true,
    ]);
    $site = Site::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Waterfront',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('reports.resource-intelligence', [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'month' => 4,
        'year' => 2026,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/ResourceIntelligence')
        ->where('reportData.scopeDisplayName', 'Test Co - Cape Town - Waterfront')
    );
});
