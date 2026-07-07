<?php

use App\Models\Company;
use App\Models\RecurringOrder;
use App\Models\ServiceProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeRecurringOrder(User $admin): RecurringOrder
{
    $company = Company::create(['name' => 'Acme Recycling']);
    $serviceProvider = ServiceProvider::create(['name' => 'WasteMart', 'types' => ['waste']]);

    return RecurringOrder::create([
        'company_id' => $company->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $admin->id,
        'order_type' => 'waste',
        'days_of_week' => ['monday'],
        'quantity_lines' => [],
        'is_active' => true,
    ]);
}

it('hides deleted recurring orders by default', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $active = makeRecurringOrder($admin);
    $deleted = makeRecurringOrder($admin);
    $deleted->delete();

    $this->actingAs($admin)
        ->get('/recurring-orders')
        ->assertInertia(fn ($page) => $page
            ->where('filters.show_deleted', false)
            ->has('recurringOrders', 1)
            ->where('recurringOrders.0.id', $active->id)
        );
});

it('shows deleted recurring orders when show_deleted is requested', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $active = makeRecurringOrder($admin);
    $deleted = makeRecurringOrder($admin);
    $deleted->delete();

    $this->actingAs($admin)
        ->get('/recurring-orders?show_deleted=1')
        ->assertInertia(fn ($page) => $page
            ->where('filters.show_deleted', true)
            ->has('recurringOrders', 2)
        );
});
