<?php

namespace App\Notifications\Channels;

use App\Services\CommunicatorEmailClient;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

class CommunicatorChannel
{
    public function __construct(
        private CommunicatorEmailClient $client
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toCommunicator')) {
            return;
        }

        /** @var array{to?: string, subject: string, text: string} $payload */
        $payload = $notification->toCommunicator($notifiable);

        $to = $payload['to'] ?? $notifiable->routeNotificationFor('mail', $notification);

        if (! is_string($to) || $to === '') {
            throw new InvalidArgumentException('No email recipient for Communicator notification.');
        }

        $this->client->send($to, $payload['subject'], $payload['text']);
    }
}
