<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Julura\LaravelCommunicator\Notifications\CommunicatorResetPasswordNotification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, CommunicatorResetPasswordNotification::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, CommunicatorResetPasswordNotification::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('reset link is sent via Communicator instead of SMTP when enabled', function () {
    config([
        'communicator.enabled' => true,
        'communicator.url' => 'https://sndng.co.za',
        'communicator.token' => 'test-token',
    ]);
    Http::fake([
        'https://sndng.co.za/api/v1/email-notifications' => Http::response(['ok' => true], 200),
    ]);

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Http::assertSent(function ($request) use ($user) {
        return $request->url() === 'https://sndng.co.za/api/v1/email-notifications'
            && $request['to'] === $user->email
            && $request['subject'] === 'Reset Password Notification';
    });
});

test('reset link goes out via mail when Communicator is disabled', function () {
    config(['communicator.enabled' => false]);
    Http::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Http::assertNothingSent();
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, CommunicatorResetPasswordNotification::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});
