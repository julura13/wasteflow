<?php

use App\Models\User;
use App\Notifications\NewUserPendingApprovalNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('register.success', absolute: false));
    $this->assertGuest();

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->is_active)->toBeFalse();
    expect($user->roles)->toHaveCount(0);
});

test('registering does not error when no admin role exists yet', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'no-admins@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('register.success', absolute: false));
});

test('all admins are notified when a new user registers', function () {
    $this->seed(RolePermissionSeeder::class);
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');
    $nonAdmin = User::factory()->create();
    $nonAdmin->assignRole('company_user');

    $this->post('/register', [
        'name' => 'Pending User',
        'email' => 'pending@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $registeredUser = User::where('email', 'pending@example.com')->first();

    Notification::assertSentTo(
        [$admin, $otherAdmin],
        NewUserPendingApprovalNotification::class,
        fn ($notification) => $notification->registeredUser->is($registeredUser)
    );
    Notification::assertNotSentTo($nonAdmin, NewUserPendingApprovalNotification::class);
});
