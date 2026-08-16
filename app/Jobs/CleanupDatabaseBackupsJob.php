<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\DatabaseBackupCleanupFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Throwable;

class CleanupDatabaseBackupsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function handle(): void
    {
        $exitCode = Artisan::call('backup:cleanup', [
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'backup:cleanup exited with code '.$exitCode.'. '.trim(Artisan::output())
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        $notification = new DatabaseBackupCleanupFailedNotification($exception->getMessage());

        foreach (config('database_backup.notify_emails', []) as $email) {
            Notification::route('mail', $email)->notify($notification);
        }

        $admins = User::role('admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }
}
