<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DatabaseBackupCleanupFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $errorMessage,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User) {
            return ['database'];
        }

        if (config('communicator.enabled', false)) {
            return ['communicator'];
        }

        return ['mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'backup',
            'badge_type' => 'error',
            'badge_label' => 'cleanup failed',
            'title' => 'Wasabi backup cleanup failed',
            'description' => $this->errorMessage,
        ];
    }

    /**
     * @return array{subject: string, text: string}
     */
    public function toCommunicator(object $notifiable): array
    {
        return [
            'subject' => $this->subjectString(),
            'text' => $this->plainTextBody(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $env = config('app.env');

        return (new MailMessage)
            ->subject($this->subjectString())
            ->greeting('Wasabi backup cleanup did not complete')
            ->line('**Environment:** '.$env)
            ->line('**Error:**')
            ->line($this->errorMessage)
            ->salutation('Old backups may keep accumulating in Wasabi until this is fixed. Investigate storage credentials, network connectivity, and the backup:cleanup command output.');
    }

    private function subjectString(): string
    {
        $app = config('app.name', 'Laravel');
        $env = config('app.env');

        return "[{$app}] URGENT: Wasabi backup cleanup failed ({$env})";
    }

    private function plainTextBody(): string
    {
        $env = config('app.env');

        return implode("\n", [
            'Wasabi backup cleanup did not complete',
            '',
            'Environment: '.$env,
            'Error:',
            $this->errorMessage,
            '',
            'Old backups may keep accumulating in Wasabi until this is fixed. Investigate storage credentials, network connectivity, and the backup:cleanup command output.',
        ]);
    }
}
