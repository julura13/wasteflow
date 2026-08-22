<?php

use App\Models\Classification;
use App\Models\Facility;
use App\Models\Grade;
use App\Models\Material;
use App\Models\WasteStream;

function materialPayload(array $overrides = []): array
{
    return array_merge([
        'waste_stream_id' => WasteStream::factory()->create()->id,
        'grade_id' => Grade::factory()->create()->id,
        'classification_id' => Classification::factory()->create()->id,
        'facility_id' => Facility::factory()->create()->id,
        'weight_required' => 'Yes',
    ], $overrides);
}

it('creates a material without a rebate', function () {
    $user = userWithPermission('manage-services');

    $response = $this->actingAs($user)->post('/materials', materialPayload());

    $response->assertRedirect('/materials');
    $this->assertDatabaseHas('materials', [
        'rebate_offered' => false,
        'rebate_rate' => null,
    ]);
});

it('requires rebate rate and client share when a rebate is offered', function () {
    $user = userWithPermission('manage-services');

    $response = $this->actingAs($user)->post('/materials', materialPayload([
        'rebate_offered' => true,
    ]));

    $response->assertSessionHasErrors(['rebate_rate', 'client_rebate_share']);
    $this->assertDatabaseCount('materials', 0);
});

it('creates a material with a rebate and rounds the rate', function () {
    $user = userWithPermission('manage-services');

    $response = $this->actingAs($user)->post('/materials', materialPayload([
        'rebate_offered' => true,
        'rebate_rate' => 12.3456,
        'client_rebate_share' => 50,
    ]));

    $response->assertRedirect('/materials');
    $this->assertDatabaseHas('materials', [
        'rebate_offered' => true,
        'rebate_rate' => 12.35,
    ]);
});

it('updates a material', function () {
    $user = userWithPermission('manage-services');
    $material = Material::factory()->create(['weight_required' => 'Yes']);

    $response = $this->actingAs($user)->put("/materials/{$material->id}", materialPayload([
        'waste_stream_id' => $material->waste_stream_id,
        'grade_id' => $material->grade_id,
        'classification_id' => $material->classification_id,
        'facility_id' => $material->facility_id,
        'weight_required' => 'No',
    ]));

    $response->assertRedirect('/materials');
    $this->assertDatabaseHas('materials', [
        'id' => $material->id,
        'weight_required' => 'No',
    ]);
});
