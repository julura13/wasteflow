<?php

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('creates an activity log entry with subject and causer', function () {
    $user = User::factory()->create();
    $company = Company::create(['name' => 'Acme Corp', 'is_active' => true]);

    $this->actingAs($user);

    $entry = ActivityLog::log('company_created', 'Company Acme Corp created', $company, [
        'name' => $company->name,
    ]);

    expect($entry)->toBeInstanceOf(ActivityLog::class)
        ->and($entry->log_name)->toBe('company_created')
        ->and($entry->description)->toBe('Company Acme Corp created')
        ->and($entry->subject_type)->toBe(Company::class)
        ->and($entry->subject_id)->toBe($company->id)
        ->and($entry->causer_id)->toBe($user->id)
        ->and($entry->properties)->toBe(['name' => 'Acme Corp']);
});

it('creates an activity log entry with null subject', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $entry = ActivityLog::log('orders_seeded', 'Order seeder ran', null, [
        'order_count' => 5,
    ]);

    expect($entry->subject_type)->toBeNull()
        ->and($entry->subject_id)->toBeNull()
        ->and($entry->log_name)->toBe('orders_seeded')
        ->and($entry->properties['order_count'])->toBe(5);
});

it('returns activity log entries oldest first when filtering by order', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $company = Company::create(['name' => 'Co', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Br', 'is_active' => true]);
    $site = Site::create(['branch_id' => $branch->id, 'name' => 'Site', 'is_active' => true]);
    $provider = ServiceProvider::create(['name' => 'Prov', 'is_active' => true]);

    $order = Order::create([
        'tracking_number' => 'WO-ORDERLOG-1',
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'site_id' => $site->id,
        'service_provider_id' => $provider->id,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => Carbon::parse('2025-12-15'),
    ]);

    $newer = ActivityLog::create([
        'log_name' => 'order_updated',
        'description' => 'Second event',
        'subject_type' => Order::class,
        'subject_id' => $order->id,
        'causer_id' => $user->id,
        'properties' => [],
    ]);
    $newer->forceFill([
        'created_at' => Carbon::parse('2025-06-02 12:00:00'),
        'updated_at' => Carbon::parse('2025-06-02 12:00:00'),
    ])->save();

    $older = ActivityLog::create([
        'log_name' => 'order_created',
        'description' => 'First event',
        'subject_type' => Order::class,
        'subject_id' => $order->id,
        'causer_id' => $user->id,
        'properties' => [],
    ]);
    $older->forceFill([
        'created_at' => Carbon::parse('2025-06-01 12:00:00'),
        'updated_at' => Carbon::parse('2025-06-01 12:00:00'),
    ])->save();

    $response = $this->actingAs($user)->get(route('activity-log.index', ['order' => 'WO-ORDERLOG-1']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('ActivityLog/Index')
        ->where('entries.0.log_name', 'order_created')
        ->where('entries.1.log_name', 'order_updated'));
});
