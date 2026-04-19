<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('soft deletes another user as admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $target = User::factory()->create();

    $response = $this->actingAs($admin)->delete(route('users.destroy', $target));

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('success');

    expect(User::find($target->id))->toBeNull();
    expect(User::withTrashed()->find($target->id)?->trashed())->toBeTrue();
});

it('does not allow deleting your own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('error');
    expect(User::find($admin->id))->not->toBeNull();
});
