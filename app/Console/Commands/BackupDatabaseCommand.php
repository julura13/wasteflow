<?php

namespace App\Console\Commands;

use App\Enums\DatabaseBackupPhase;
use App\Models\User;
use App\Notifications\DatabaseBackupStatusNotification;
use App\Services\DatabaseWasabiBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database
                            {--connection= : Database connection name (defaults to the default connection)}
                            {--no-notify : Skip email notifications}
                            {--no-compress : Upload an uncompressed .sql file}';

    protected $description = 'Dump the database, optionally gzip it, and upload the backup to object storage (Wasabi / S3-compatible)';

    public function handle(DatabaseWasabiBackupService $service): int
    {
        $connection = $this->option('connection') ?: (string) config('database.default');
        $noNotify = (bool) $this->option('no-notify');

        if ($this->option('no-compress')) {
            config(['database_backup.compress' => false]);
        }

        $runId = (string) Str::uuid();
        $started = hrtime(true);
        $driver = (string) (config('database.connections.'.$connection.'.driver') ?? 'unknown');

        $emails = $noNotify ? [] : config('database_backup.notify_emails', []);

        $this->sendNotifications($emails, new DatabaseBackupStatusNotification(
            DatabaseBackupPhase::Started,
            $runId,
            $connection,
            $driver,
        ));

        try {
            $result = $service->run($connection, $runId);
        } catch (Throwable $e) {
            $duration = (hrtime(true) - $started) / 1e9;

            $this->sendNotifications($emails, new DatabaseBackupStatusNotification(
                DatabaseBackupPhase::Failed,
                $runId,
                $connection,
                $driver,
                durationSeconds: $duration,
                errorMessage: $e->getMessage(),
                errorClass: $e::class,
                recentLogLines: $this->contextLines($e),
            ));

            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $duration = (hrtime(true) - $started) / 1e9;

        $this->sendNotifications($emails, new DatabaseBackupStatusNotification(
            DatabaseBackupPhase::Succeeded,
            $runId,
            $connection,
            $driver,
            durationSeconds: $duration,
            remotePath: $result->remotePath,
            bytesStored: $result->bytesStored,
        ));

        $this->info("Backup uploaded to {$result->remotePath}");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $emails
     */
    private function sendNotifications(array $emails, DatabaseBackupStatusNotification $notification): void
    {
        foreach ($emails as $email) {
            Notification::route('mail', $email)->notify($notification);
        }

        $admins = User::role('admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }

    /**
     * @return array<int, string>
     */
    private function contextLines(Throwable $e): array
    {
        $trace = $e->getTraceAsString();
        $lines = preg_split("/\r\n|\n|\r/", $trace) ?: [];

        return array_values(array_filter(array_slice($lines, 0, 12)));
    }
}
