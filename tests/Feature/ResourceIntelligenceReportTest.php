<?php

use App\Models\Company;
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
        ->where('filters.company_id', $company->id)
    );
});
