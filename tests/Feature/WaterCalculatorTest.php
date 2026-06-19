<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('displays water calculator when calculator permission is granted', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->givePermissionTo('view-water-calculator');

    $response = $this->actingAs($user)->get(route('reports.water-calculator'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Reports/WaterCalculator'));
});

it('returns water breakdown from posted weights', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->givePermissionTo('view-water-calculator');

    $response = $this->actingAs($user)->postJson(route('reports.water-calculator.calculate'), [
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
            'paper' => ['total', 'factorLPerKg', 'waterSavedLitres'],
            'totalLitres',
            'totalKilolitres',
        ],
    ]);
    expect($response->json('breakdown.paper.waterSavedLitres'))->toEqual(180000.0);
    expect($response->json('breakdown.totalKilolitres'))->toEqual(180.0);
});

it('forbids water calculator GET/POST without calculator permission', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $getResponse = $this->actingAs($user)->get(route('reports.water-calculator'));
    $getResponse->assertForbidden();

    $postResponse = $this->actingAs($user)->postJson(route('reports.water-calculator.calculate'), [
        'weights' => ['paper' => 100],
    ]);
    $postResponse->assertForbidden();
});

it('rejects calculation when weights are missing', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->givePermissionTo('view-water-calculator');

    $response = $this->actingAs($user)->postJson(route('reports.water-calculator.calculate'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['weights']);
});
