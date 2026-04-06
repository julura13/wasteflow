<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('includes orders from yesterday through seven days ahead in dashboard quick view and exposes quantity lines', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-10 12:00:00'));

    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Quick View Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Branch', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $serviceProvider = \App\Models\ServiceProvider::create(['name' => 'Provider QV', 'is_active' => true]);

    $yesterday = Carbon::today()->subDay();
    $plusSeven = Carbon::today()->addDays(7);
    $plusEight = Carbon::today()->addDays(8);

    $lines = [
        ['quantity' => 1, 'quantity_type' => 'cage_8m3', 'container_option_name' => '8m³ Cage'],
    ];

    $orderInWindowA = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'scheduled',
        'requested_collection_date' => $yesterday,
        'quantity_lines' => $lines,
    ]);

    $orderInWindowB = Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'scheduled',
        'requested_collection_date' => $plusSeven,
        'quantity_lines' => [],
        'quantity' => 2,
        'quantity_type' => 'loose_bags',
    ]);

    Order::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $serviceProvider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'scheduled',
        'requested_collection_date' => $plusEight,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard', [
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('ordersNearDates', 2)
        ->where('ordersNearDates', function ($orders) use ($orderInWindowA, $orderInWindowB) {
            $ids = collect($orders)->pluck('id')->sort()->values()->all();
            expect($ids)->toBe([$orderInWindowA->id, $orderInWindowB->id]);

            $a = collect($orders)->firstWhere('id', $orderInWindowA->id);
            expect($a['quantity_lines'])->toBeArray()->not->toBeEmpty()
                ->and($a['quantity_lines'][0]['container_option_name'])->toBe('8m³ Cage');

            $b = collect($orders)->firstWhere('id', $orderInWindowB->id);
            expect($b['quantity'])->toBe(2)
                ->and($b['quantity_type'])->toBe('loose_bags');

            return true;
        })
    );
});
