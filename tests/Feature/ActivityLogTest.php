<?php

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
