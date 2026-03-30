<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('displays landfill space calculator when calculator permission is granted', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->givePermissionTo('view-landfill-space-calculator');

    $response = $this->actingAs($user)->get(route('reports.landfill-space-calculator'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Reports/LandfillSpaceCalculator'));
});

it('returns landfill breakdown from posted weights', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->givePermissionTo('view-landfill-space-calculator');

    $response = $this->actingAs($user)->postJson(route('reports.landfill-space-calculator.calculate'), [
        'weights' => [
            'paper' => 100,
            'plastics' => 0,
            'aluminium' => 0,
            'steel' => 0,
            'glass' => 0,
            'tetrapak' => 0,
            'organics' => 0,
        ],
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'breakdown' => [
            'paper' => ['total', 'densityKgPerM3', 'spaceSaved'],
            'total',
        ],
    ]);
    expect($response->json('breakdown.paper.spaceSaved'))->toBe(1.0);
    expect($response->json('breakdown.total'))->toBe(1.0);
});

it('forbids landfill space calculator GET/POST without calculator permission', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $getResponse = $this->actingAs($user)->get(route('reports.landfill-space-calculator'));
    $getResponse->assertForbidden();

    $postResponse = $this->actingAs($user)->postJson(route('reports.landfill-space-calculator.calculate'), [
        'weights' => ['paper' => 100],
    ]);
    $postResponse->assertForbidden();
});

it('rejects calculation when weights are missing', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->givePermissionTo('view-landfill-space-calculator');

    $response = $this->actingAs($user)->postJson(route('reports.landfill-space-calculator.calculate'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['weights']);
});
