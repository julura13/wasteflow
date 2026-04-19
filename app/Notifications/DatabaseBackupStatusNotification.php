<?php

namespace App\Notifications;

use App\Enums\DatabaseBackupPhase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DatabaseBackupStatusNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>|null  $recentLogLines
     */
    public function __construct(
        public DatabaseBackupPhase $phase,
        public string $runId,
        public string $connectionName,
        public string $driver,
        public ?float $durationSeconds = null,
        public ?string $remotePath = null,
        public ?int $bytesStored = null,
        public ?string $errorMessage = null,
        public ?string $errorClass = null,
        public ?array $recentLogLines = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = config('app.name', 'Laravel');
        $env = config('app.env');

        $subject = match ($this->phase) {
            DatabaseBackupPhase::Started => "[{$app}] Database backup started ({$env})",
            DatabaseBackupPhase::Succeeded => "[{$app}] Database backup completed ({$env})",
            DatabaseBackupPhase::Failed => "[{$app}] URGENT: Database backup failed ({$env})",
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($this->greetingLine());

        $mail->line('**Run ID:** '.$this->runId)
            ->line('**Connection:** '.$this->connectionName)
            ->line('**Driver:** '.$this->driver)
            ->line('**Environment:** '.$env)
            ->line('**Application URL:** '.config('app.url'));

        if ($this->phase === DatabaseBackupPhase::Started) {
            $mail->line('A full logical dump has been started and will be uploaded to object storage when finished.');
        }

        if ($this->phase === DatabaseBackupPhase::Succeeded) {
            $mail->line('**Duration:** '.number_format((float) $this->durationSeconds, 2).' s')
                ->line('**Remote path:** '.$this->remotePath)
                ->line('**Stored size:** '.$this->formatBytes((int) $this->bytesStored))
                ->line('The backup file is stored on the configured S3-compatible disk (e.g. Wasabi) under the path above.');
        }

        if ($this->phase === DatabaseBackupPhase::Failed) {
            if ($this->durationSeconds !== null) {
                $mail->line('**Elapsed before failure:** '.number_format((float) $this->durationSeconds, 2).' s');
            }

            $mail->line('**Error class:** '.($this->errorClass ?? 'unknown'))
                ->line('**Message:**')
                ->line($this->errorMessage ?? '(no message)');

            if ($this->recentLogLines !== null && $this->recentLogLines !== []) {
                $mail->line('**Recent context:**');
                foreach ($this->recentLogLines as $line) {
                    $mail->line($line);
                }
            }

            $mail->salutation('Investigate storage credentials, database connectivity, and server disk space.');
        }

        return $mail;
    }

    private function greetingLine(): string
    {
        return match ($this->phase) {
            DatabaseBackupPhase::Started => 'Database backup started',
            DatabaseBackupPhase::Succeeded => 'Database backup finished successfully',
            DatabaseBackupPhase::Failed => 'Database backup did not complete',
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024)) - 1;
        $i = max(0, min($i, count($units) - 1));

        return number_format($bytes / (1024 ** ($i + 1)), 2).' '.$units[$i];
    }
}
