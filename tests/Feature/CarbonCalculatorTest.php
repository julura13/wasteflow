<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('displays carbon calculator page for user with view-reports', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('reports.carbon-calculator'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Reports/CarbonCalculator'));
});

it('returns carbon calculation from posted weights', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $weights = [
        'paper' => 85,
        'plasticPPHD' => 35,
        'plasticPS' => 0,
        'plasticLDPE' => 0,
        'aluminium' => 0,
        'steel' => 0,
        'glass' => 0,
        'foodWaste' => 0,
        'gardenWaste' => 0,
        'batteries' => 0,
        'electronics' => 0,
        'tetrapak' => 0,
    ];

    $response = $this->actingAs($user)->postJson(route('reports.carbon-calculator.calculate'), [
        'weights' => $weights,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'materials' => [
            ['material', 'weight', 'scope3EF', 'landfillAvoidanceEF', 'otherOffsets', 'lifecycleSaving'],
        ],
        'totals' => ['scope3EF', 'landfillAvoidanceEF', 'otherOffsets', 'lifecycleSaving'],
    ]);
    // Paper 85 kg: scope3 = 85*0.5 = 42.5, landfill = 85*0.78 = 66.3, lifecycle = 108.8
    expect($response->json('totals.lifecycleSaving'))->toBeGreaterThan(0);
    expect($response->json('totals.scope3EF'))->toBe(42.5);
    expect($response->json('totals.landfillAvoidanceEF'))->toBe(66.3);
    expect($response->json('totals.lifecycleSaving'))->toBe(108.8);
});

it('rejects carbon calculation when weights are missing', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->postJson(route('reports.carbon-calculator.calculate'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['weights']);
});
