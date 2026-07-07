<?php

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('finds users by matching company name via the belongs-to company', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $company = Company::create(['name' => 'Acme Recycling']);
    $matching = User::factory()->create(['company_id' => $company->id]);
    $other = User::factory()->create();

    $this->actingAs($admin)
        ->get('/users?search=Acme')
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $matching->id)
        );
});

it('finds users by matching company name via the many-to-many companies pivot', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $company = Company::create(['name' => 'Greenline Waste']);
    $matching = User::factory()->create();
    $matching->companies()->attach($company->id, ['role' => 'viewer']);
    $other = User::factory()->create();

    $this->actingAs($admin)
        ->get('/users?search=Greenline')
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $matching->id)
        );
});

it('still filters by name, email, and active status correctly alongside company search', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $activeMatch = User::factory()->create(['name' => 'Jane Searchable', 'is_active' => true]);
    $inactiveMatch = User::factory()->create(['name' => 'Jane Searchable Two', 'is_active' => false]);
    User::factory()->create(['name' => 'Unrelated Person']);

    $this->actingAs($admin)
        ->get('/users?search=Searchable&active=1')
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $activeMatch->id)
        );
});
