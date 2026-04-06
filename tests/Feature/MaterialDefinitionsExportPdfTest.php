<?php

use App\Models\Classification;
use App\Models\Facility;
use App\Models\Grade;
use App\Models\Material;
use App\Models\User;
use App\Models\WasteStream;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('exports material definitions as PDF for users with manage-services', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $wasteStream = WasteStream::create(['name' => 'WS Export Pdf', 'is_active' => true]);
    $grade = Grade::create(['name' => 'Grade Export Pdf', 'is_active' => true]);
    $classification = Classification::create(['name' => 'Recycling', 'is_active' => true]);
    $facility = Facility::create([
        'name' => 'Facility Export Pdf',
        'facility_type' => 'recycling',
        'requires_weight' => true,
        'is_active' => true,
    ]);

    Material::create([
        'waste_stream_id' => $wasteStream->id,
        'grade_id' => $grade->id,
        'classification_id' => $classification->id,
        'facility_id' => $facility->id,
        'service_provider_id' => null,
        'weight_required' => 'Yes',
        'rebate_offered' => true,
        'rebate_rate' => 1.5,
        'client_rebate_share' => 70,
        'backing_document' => false,
        'notes' => 'Export row note',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('materials.export.pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('forbids material definitions PDF without manage-services', function () {
    $user = User::factory()->create();
    $user->assignRole('client');

    $this->actingAs($user)->get(route('materials.export.pdf'))->assertForbidden();
});
