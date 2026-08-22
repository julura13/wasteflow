<?php

use App\Models\ServiceProvider;

it('creates a service provider with valid data', function () {
    $user = userWithPermission('manage-services');

    $response = $this->actingAs($user)->post('/service-providers', [
        'name' => 'Green Recyclers',
        'types' => ['recycling'],
        'slip_number_prefix' => '  GR-  ',
    ]);

    $response->assertRedirect('/service-providers');
    $this->assertDatabaseHas('service_providers', [
        'name' => 'Green Recyclers',
        'is_active' => true,
        'slip_number_prefix' => 'GR-',
    ]);
});

it('rejects a service provider without a type', function () {
    $user = userWithPermission('manage-services');

    $response = $this->actingAs($user)->post('/service-providers', [
        'name' => 'Green Recyclers',
        'types' => [],
    ]);

    $response->assertSessionHasErrors('types');
    $this->assertDatabaseCount('service_providers', 0);
});

it('updates a service provider and blanks an empty slip number prefix', function () {
    $user = userWithPermission('manage-services');
    $serviceProvider = ServiceProvider::factory()->create(['slip_number_prefix' => 'OLD']);

    $response = $this->actingAs($user)->put("/service-providers/{$serviceProvider->id}", [
        'name' => 'Updated Recyclers',
        'types' => ['general'],
        'slip_number_prefix' => '   ',
    ]);

    $response->assertRedirect('/service-providers');
    $this->assertDatabaseHas('service_providers', [
        'id' => $serviceProvider->id,
        'name' => 'Updated Recyclers',
        'slip_number_prefix' => null,
    ]);
});
