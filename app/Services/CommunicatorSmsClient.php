<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class CommunicatorSmsClient
{
    public function send(string $to, string $message): void
    {
        $token = config('communicator.token');
        if (! is_string($token) || $token === '') {
            throw new InvalidArgumentException('COMMUNICATOR_API_TOKEN is not set.');
        }

        $baseUrl = (string) config('communicator.url', 'https://sndng.co.za');
        $url = rtrim($baseUrl, '/').'/api/v1/sms-notifications';

        $response = Http::withToken($token)
            ->asForm()
            ->acceptJson()
            ->timeout(30)
            ->post($url, [
                'to' => $to,
                'message' => $message,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Communicator SMS API request failed ('.$response->status().'): '.$response->body()
            );
        }
    }
}
