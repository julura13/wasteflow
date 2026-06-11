<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\RecurringOrder;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeTemplate(array $days, bool $isActive = true): RecurringOrder
{
    $user = User::factory()->create();
    $company = Company::create(['name' => 'RC Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'B', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'S', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'P', 'is_active' => true]);

    return RecurringOrder::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'days_of_week' => $days,
        'quantity_lines' => [
            ['container_option_id' => 1, 'container_option_name' => '240l Bin', 'quantity' => 2],
        ],
        'notes' => null,
        'is_active' => $isActive,
    ]);
}

it('creates a pending order for active templates matching the given day', function () {
    $monday = Carbon::parse('next monday');
    makeTemplate(['monday']);

    $this->artisan('recurring-orders:create', ['--date' => $monday->toDateString()])
        ->assertSuccessful();

    expect(Order::count())->toBe(1);
    $order = Order::first();
    expect($order->status)->toBe('pending');
    expect($order->requested_collection_date->toDateString())->toBe($monday->toDateString());
    expect($order->recurring_order_id)->not->toBeNull();
});

it('skips templates that do not match the day', function () {
    $monday = Carbon::parse('next monday');
    makeTemplate(['tuesday', 'wednesday']); // does not include monday

    $this->artisan('recurring-orders:create', ['--date' => $monday->toDateString()])
        ->assertSuccessful();

    expect(Order::count())->toBe(0);
});

it('skips inactive templates', function () {
    $monday = Carbon::parse('next monday');
    makeTemplate(['monday'], isActive: false);

    $this->artisan('recurring-orders:create', ['--date' => $monday->toDateString()])
        ->assertSuccessful();

    expect(Order::count())->toBe(0);
});

it('does not create a duplicate order if run twice on the same day', function () {
    $monday = Carbon::parse('next monday');
    makeTemplate(['monday']);

    $this->artisan('recurring-orders:create', ['--date' => $monday->toDateString()])->assertSuccessful();
    $this->artisan('recurring-orders:create', ['--date' => $monday->toDateString()])->assertSuccessful();

    expect(Order::count())->toBe(1);
});
