<?php

use App\Services\CommunicatorSmsClient;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

it('posts form data to the communicator sms endpoint with a bearer token', function () {
    config([
        'communicator.url' => 'https://sndng.co.za',
        'communicator.token' => 'test-token',
    ]);

    Http::fake([
        'https://sndng.co.za/api/v1/sms-notifications' => Http::response(['ok' => true], 200),
    ]);

    (new CommunicatorSmsClient)->send('27817878984', 'Scheduled orders created');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sndng.co.za/api/v1/sms-notifications'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === '27817878984'
            && $request['message'] === 'Scheduled orders created';
    });
});

it('throws when the api returns an error status', function () {
    config([
        'communicator.url' => 'https://sndng.co.za',
        'communicator.token' => 'test-token',
    ]);

    Http::fake([
        'https://sndng.co.za/api/v1/sms-notifications' => Http::response(['error' => 'nope'], 422),
    ]);

    (new CommunicatorSmsClient)->send('27817878984', 'T');
})->throws(RuntimeException::class);
