<?php

use App\Models\Company;

it('creates a company with valid data', function () {
    $user = userWithPermission('manage-clients');

    $response = $this->actingAs($user)->post('/companies', [
        'name' => 'Acme Waste',
        'email' => 'contact@acme.test',
        'rebate_percentage' => 12.5,
        'is_active' => true,
    ]);

    $response->assertRedirect('/companies');
    $this->assertDatabaseHas('companies', [
        'name' => 'Acme Waste',
        'email' => 'contact@acme.test',
    ]);
});

it('rejects a company without a name', function () {
    $user = userWithPermission('manage-clients');

    $response = $this->actingAs($user)->post('/companies', [
        'email' => 'contact@acme.test',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseCount('companies', 0);
});

it('updates a company with valid data', function () {
    $user = userWithPermission('manage-clients');
    $company = Company::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($user)->put("/companies/{$company->id}", [
        'name' => 'New Name',
        'is_active' => true,
    ]);

    $response->assertRedirect('/companies');
    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'name' => 'New Name',
    ]);
});
