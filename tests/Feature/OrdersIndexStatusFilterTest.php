<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('remembers the status filter in session when a non-empty status is requested', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get(route('orders.index', ['status' => 'pending']));

    $response->assertOk();
    $response->assertSessionHas('orders_status_filter', 'pending');
    $response->assertInertia(fn ($page) => $page->where('filters.status', 'pending'));
});

it('clears the remembered status filter when the status query parameter is present but empty', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)->get(route('orders.index', ['status' => 'pending']));

    $response = $this->actingAs($user)->get('/orders?status=');

    $response->assertOk();
    $response->assertSessionMissing('orders_status_filter');
    $response->assertInertia(fn ($page) => $page->where('filters.status', null));
});
