<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\RecurringOrder;
use App\Models\ServiceProvider;
use App\Models\Site;
use App\Models\User;
use App\Notifications\RecurringOrdersSummaryNotification;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

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

it('defaults to tomorrow when no date option is given', function () {
    $tomorrow = Carbon::tomorrow();
    makeTemplate([strtolower($tomorrow->format('l'))]);

    $this->artisan('recurring-orders:create')
        ->assertSuccessful();

    expect(Order::count())->toBe(1);
    expect(Order::first()->requested_collection_date->toDateString())->toBe($tomorrow->toDateString());
});

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

it('sends the summary via mail when communicator is disabled', function () {
    Notification::fake();
    config([
        'communicator.enabled' => false,
        'recurring_orders.notify_emails' => ['ops@example.com'],
        'recurring_orders.sms_to' => null,
    ]);

    $monday = Carbon::parse('next monday');
    makeTemplate(['monday']);

    $this->artisan('recurring-orders:create', ['--date' => $monday->toDateString()])->assertSuccessful();

    Notification::assertSentOnDemand(
        RecurringOrdersSummaryNotification::class,
        fn ($notification, $channels) => in_array('mail', $channels, true)
    );
});

it('sends the summary via communicator and an sms when both are configured', function () {
    Notification::fake();
    Http::fake([
        'https://sndng.co.za/api/v1/sms-notifications' => Http::response(['ok' => true], 200),
    ]);

    config([
        'communicator.enabled' => true,
        'communicator.url' => 'https://sndng.co.za',
        'communicator.token' => 'test-token',
        'recurring_orders.notify_emails' => ['ops@example.com'],
        'recurring_orders.sms_to' => '27817878984',
    ]);

    $monday = Carbon::parse('next monday');
    makeTemplate(['monday']);

    $this->artisan('recurring-orders:create', ['--date' => $monday->toDateString()])->assertSuccessful();

    Notification::assertSentOnDemand(
        RecurringOrdersSummaryNotification::class,
        fn ($notification, $channels) => in_array('communicator', $channels, true)
    );

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sndng.co.za/api/v1/sms-notifications'
            && $request['to'] === '27817878984'
            && str_contains((string) $request['message'], 'scheduled recurring orders created');
    });
});

it('skips the sms when no recipient is configured', function () {
    Notification::fake();
    Http::fake();

    config([
        'communicator.enabled' => true,
        'recurring_orders.notify_emails' => [],
        'recurring_orders.sms_to' => null,
    ]);

    $monday = Carbon::parse('next monday');
    makeTemplate(['monday']);

    $this->artisan('recurring-orders:create', ['--date' => $monday->toDateString()])->assertSuccessful();

    Http::assertNothingSent();
});
