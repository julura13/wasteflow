<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('persists a description when creating a role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post('/roles', [
            'name' => 'auditor',
            'description' => 'Read-only access for compliance audits.',
            'permissions' => ['view-reports'],
        ])
        ->assertRedirect(route('roles.index'));

    $role = Role::where('name', 'auditor')->first();
    expect($role->description)->toBe('Read-only access for compliance audits.');
});

it('persists a description when updating a role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $role = Role::where('name', 'client')->first();

    $this->actingAs($admin)
        ->put("/roles/{$role->id}", [
            'name' => 'client',
            'description' => 'Updated description.',
            'permissions' => ['view-dashboard'],
        ])
        ->assertRedirect(route('roles.index'));

    expect($role->fresh()->description)->toBe('Updated description.');
});

it('does not seed the dead view-orders, create-orders, or manage-permissions permissions', function () {
    expect(Permission::whereIn('name', ['view-orders', 'create-orders', 'manage-permissions'])->count())->toBe(0);
});

it('shows role descriptions on the roles index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/roles')
        ->assertInertia(fn ($page) => $page
            ->where('roles', fn ($roles) => collect($roles)->firstWhere('name', 'admin')['description'] !== null)
        );
});
