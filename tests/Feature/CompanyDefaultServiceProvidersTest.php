<?php

use App\Models\Company;
use App\Models\ServiceProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('stores optional default service providers on company create', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $wasteSp = ServiceProvider::create(['name' => 'Waste SP', 'types' => ['waste_collection'], 'is_active' => true]);
    $recyclingSp = ServiceProvider::create(['name' => 'Recycling SP', 'types' => ['recycling'], 'is_active' => true]);

    $this->actingAs($user)->post(route('companies.store'), [
        'name' => 'Acme',
        'is_active' => true,
        'default_waste_service_provider_id' => $wasteSp->id,
        'default_recycling_service_provider_id' => $recyclingSp->id,
    ])->assertRedirect(route('companies.index'));

    $company = Company::where('name', 'Acme')->first();
    expect($company)->not->toBeNull()
        ->and($company->default_waste_service_provider_id)->toBe($wasteSp->id)
        ->and($company->default_recycling_service_provider_id)->toBe($recyclingSp->id);
});

it('updates company default service providers and allows clearing them', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $wasteSp = ServiceProvider::create(['name' => 'Waste SP', 'types' => ['general'], 'is_active' => true]);
    $recyclingSp = ServiceProvider::create(['name' => 'Recycling SP', 'types' => ['general'], 'is_active' => true]);

    $company = Company::create([
        'name' => 'Co',
        'is_active' => true,
        'default_waste_service_provider_id' => $wasteSp->id,
        'default_recycling_service_provider_id' => $recyclingSp->id,
    ]);

    $this->actingAs($user)->put(route('companies.update', $company), [
        'name' => 'Co',
        'is_active' => true,
        'default_waste_service_provider_id' => null,
        'default_recycling_service_provider_id' => null,
    ])->assertRedirect(route('companies.index'));

    $company->refresh();
    expect($company->default_waste_service_provider_id)->toBeNull()
        ->and($company->default_recycling_service_provider_id)->toBeNull();
});

it('passes active service providers to company create and edit forms', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    ServiceProvider::create(['name' => 'Active SP', 'is_active' => true]);
    ServiceProvider::create(['name' => 'Inactive SP', 'is_active' => false]);

    $this->actingAs($user)->get(route('companies.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Companies/Create')
            ->has('serviceProviders', 1)
            ->where('serviceProviders.0.name', 'Active SP'));

    $company = Company::create(['name' => 'Edit Co', 'is_active' => true]);

    $this->actingAs($user)->get(route('companies.edit', $company))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Companies/Edit')
            ->has('serviceProviders', 1)
            ->where('serviceProviders.0.name', 'Active SP'));
});

it('includes company default service provider ids on order create page', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $wasteSp = ServiceProvider::create(['name' => 'Waste SP', 'types' => ['general'], 'is_active' => true]);
    Company::create([
        'name' => 'Order Co',
        'is_active' => true,
        'default_waste_service_provider_id' => $wasteSp->id,
    ]);

    $this->actingAs($user)->get(route('orders.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Orders/Create')
            ->has('companies', 1)
            ->where('companies.0.default_waste_service_provider_id', $wasteSp->id));
});
