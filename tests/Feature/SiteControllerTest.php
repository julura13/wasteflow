<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Site;
use Inertia\Testing\AssertableInertia as Assert;

it('creates a collection point with valid data', function () {
    $user = userWithPermission('manage-clients');
    $branch = Branch::factory()->create();

    $response = $this->actingAs($user)->post('/collection-points', [
        'branch_id' => $branch->id,
        'name' => 'Main Depot',
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('sites', [
        'branch_id' => $branch->id,
        'name' => 'Main Depot',
    ]);
});

it('rejects a collection point without a branch', function () {
    $user = userWithPermission('manage-clients');

    $response = $this->actingAs($user)->post('/collection-points', [
        'name' => 'Main Depot',
    ]);

    $response->assertSessionHasErrors('branch_id');
    $this->assertDatabaseCount('sites', 0);
});

it('updates a collection point with valid data', function () {
    $user = userWithPermission('manage-clients');
    $site = Site::factory()->create(['name' => 'Old Depot']);

    $response = $this->actingAs($user)->put("/collection-points/{$site->id}", [
        'branch_id' => $site->branch_id,
        'name' => 'New Depot',
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('sites', [
        'id' => $site->id,
        'name' => 'New Depot',
    ]);
});

it('only lists active companies and branches in the site filters', function () {
    $user = userWithPermission('manage-clients');
    $activeCompany = Company::factory()->create(['is_active' => true]);
    Company::factory()->create(['is_active' => false]);
    $activeBranch = Branch::factory()->create(['company_id' => $activeCompany->id, 'is_active' => true]);
    Branch::factory()->create(['company_id' => $activeCompany->id, 'is_active' => false]);

    $response = $this->actingAs($user)->get('/collection-points');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('CollectionPoints/Index', shouldExist: false)
        ->has('companies', 1)
        ->where('companies.0.id', $activeCompany->id)
        ->has('branches', 1)
        ->where('branches.0.id', $activeBranch->id)
    );
});
