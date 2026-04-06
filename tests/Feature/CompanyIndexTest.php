<?php

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('returns all companies on the index without pagination', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    foreach (range(1, 15) as $i) {
        Company::create([
            'name' => sprintf('Company %02d', $i),
            'is_active' => true,
        ]);
    }

    $response = $this->actingAs($user)->get(route('companies.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Companies/Index')
        ->has('companies', 15)
        ->where('companies.0.name', 'Company 01')
        ->where('companies.14.name', 'Company 15')
        ->missing('companies.data')
        ->missing('companies.links'));
});
