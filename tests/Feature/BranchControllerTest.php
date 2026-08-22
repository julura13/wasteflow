<?php

use App\Models\Branch;
use App\Models\Company;
use Inertia\Testing\AssertableInertia as Assert;

it('creates a branch with valid data', function () {
    $user = userWithPermission('manage-clients');
    $company = Company::factory()->create();

    $response = $this->actingAs($user)->post('/branches', [
        'company_id' => $company->id,
        'name' => 'Cape Town Branch',
        'is_active' => true,
    ]);

    $response->assertRedirect('/branches');
    $this->assertDatabaseHas('branches', [
        'company_id' => $company->id,
        'name' => 'Cape Town Branch',
    ]);
});

it('rejects a branch without a company', function () {
    $user = userWithPermission('manage-clients');

    $response = $this->actingAs($user)->post('/branches', [
        'name' => 'Cape Town Branch',
    ]);

    $response->assertSessionHasErrors('company_id');
    $this->assertDatabaseCount('branches', 0);
});

it('updates a branch with valid data', function () {
    $user = userWithPermission('manage-clients');
    $branch = Branch::factory()->create(['name' => 'Old Branch']);

    $response = $this->actingAs($user)->put("/branches/{$branch->id}", [
        'company_id' => $branch->company_id,
        'name' => 'New Branch',
        'is_active' => true,
    ]);

    $response->assertRedirect('/branches');
    $this->assertDatabaseHas('branches', [
        'id' => $branch->id,
        'name' => 'New Branch',
    ]);
});

it('only lists active companies in the branch filter dropdown', function () {
    $user = userWithPermission('manage-clients');
    $activeCompany = Company::factory()->create(['is_active' => true]);
    Company::factory()->create(['is_active' => false]);

    $response = $this->actingAs($user)->get('/branches');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Branches/Index')
        ->has('companies', 1)
        ->where('companies.0.id', $activeCompany->id)
    );
});
