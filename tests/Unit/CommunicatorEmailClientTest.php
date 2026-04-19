<?php

use App\Services\CommunicatorEmailClient;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

it('posts form data to the communicator email endpoint with a bearer token', function () {
    config([
        'communicator.url' => 'https://sndng.co.za',
        'communicator.token' => 'test-token',
    ]);

    Http::fake([
        'https://sndng.co.za/api/v1/email-notifications' => Http::response(['ok' => true], 200),
    ]);

    (new CommunicatorEmailClient)->send('a@example.com', 'Subject line', "Line one\nLine two");

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sndng.co.za/api/v1/email-notifications'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['to'] === 'a@example.com'
            && $request['subject'] === 'Subject line'
            && $request['text'] === "Line one\nLine two";
    });
});

it('throws when the api returns an error status', function () {
    config([
        'communicator.url' => 'https://sndng.co.za',
        'communicator.token' => 'test-token',
    ]);

    Http::fake([
        'https://sndng.co.za/api/v1/email-notifications' => Http::response(['error' => 'nope'], 422),
    ]);

    (new CommunicatorEmailClient)->send('a@example.com', 'S', 'T');
})->throws(RuntimeException::class);
