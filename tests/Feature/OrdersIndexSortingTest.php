<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\ServiceProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('admin');
    $this->serviceProvider = ServiceProvider::create(['name' => 'Provider', 'is_active' => true]);
});

it('returns sort_by and sort_dir in filters when provided', function () {
    $response = $this->actingAs($this->user)
        ->get(route('orders.index', ['sort_by' => 'company', 'sort_dir' => 'asc']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_by', 'company')
        ->where('filters.sort_dir', 'asc')
    );
});

it('ignores invalid sort_by values and falls back to default', function () {
    $response = $this->actingAs($this->user)
        ->get(route('orders.index', ['sort_by' => 'invalid_column', 'sort_dir' => 'asc']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('filters.sort_by', null));
});

it('sorts orders by company name ascending', function () {
    $companyA = Company::create(['name' => 'Alpha Corp', 'is_active' => true]);
    $companyZ = Company::create(['name' => 'Zeta Ltd', 'is_active' => true]);
    $branchA = Branch::create(['company_id' => $companyA->id, 'name' => 'Branch A', 'is_active' => true]);
    $branchZ = Branch::create(['company_id' => $companyZ->id, 'name' => 'Branch Z', 'is_active' => true]);

    Order::create(['company_id' => $companyZ->id, 'branch_id' => $branchZ->id, 'service_provider_id' => $this->serviceProvider->id, 'order_type' => 'waste', 'status' => 'pending', 'requested_collection_date' => now(), 'created_by' => $this->user->id]);
    Order::create(['company_id' => $companyA->id, 'branch_id' => $branchA->id, 'service_provider_id' => $this->serviceProvider->id, 'order_type' => 'waste', 'status' => 'pending', 'requested_collection_date' => now(), 'created_by' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->get(route('orders.index', ['sort_by' => 'company', 'sort_dir' => 'asc']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.sort_by', 'company')
        ->where('filters.sort_dir', 'asc')
        ->has('orders.data', 2)
    );

    $data = $response->original->getData()['page']['props']['orders']['data'];
    expect($data[0]['company_id'])->toBe($companyA->id)
        ->and($data[1]['company_id'])->toBe($companyZ->id);
});

it('sorts orders by company name descending', function () {
    $companyA = Company::create(['name' => 'Alpha Corp', 'is_active' => true]);
    $companyZ = Company::create(['name' => 'Zeta Ltd', 'is_active' => true]);
    $branchA = Branch::create(['company_id' => $companyA->id, 'name' => 'Branch A', 'is_active' => true]);
    $branchZ = Branch::create(['company_id' => $companyZ->id, 'name' => 'Branch Z', 'is_active' => true]);

    Order::create(['company_id' => $companyA->id, 'branch_id' => $branchA->id, 'service_provider_id' => $this->serviceProvider->id, 'order_type' => 'waste', 'status' => 'pending', 'requested_collection_date' => now(), 'created_by' => $this->user->id]);
    Order::create(['company_id' => $companyZ->id, 'branch_id' => $branchZ->id, 'service_provider_id' => $this->serviceProvider->id, 'order_type' => 'waste', 'status' => 'pending', 'requested_collection_date' => now(), 'created_by' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->get(route('orders.index', ['sort_by' => 'company', 'sort_dir' => 'desc']));

    $response->assertOk();

    $data = $response->original->getData()['page']['props']['orders']['data'];
    expect($data[0]['company_id'])->toBe($companyZ->id)
        ->and($data[1]['company_id'])->toBe($companyA->id);
});
