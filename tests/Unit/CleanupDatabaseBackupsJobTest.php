<?php

use App\Jobs\CleanupDatabaseBackupsJob;
use App\Models\User;
use App\Notifications\DatabaseBackupCleanupFailedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('delegates to the backup:cleanup artisan command', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:cleanup', ['--no-interaction' => true])
        ->andReturn(0);

    (new CleanupDatabaseBackupsJob)->handle();
});

it('throws when the command exits unsuccessfully', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:cleanup', ['--no-interaction' => true])
        ->andReturn(1);

    Artisan::shouldReceive('output')->andReturn('error output');

    (new CleanupDatabaseBackupsJob)->handle();
})->throws(RuntimeException::class);

it('notifies admins and configured emails when the job ultimately fails', function () {
    Notification::fake();

    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    config(['database_backup.notify_emails' => ['ops@example.com']]);

    (new CleanupDatabaseBackupsJob)->failed(new RuntimeException('disk unreachable'));

    Notification::assertSentTo(
        $admin,
        DatabaseBackupCleanupFailedNotification::class,
        fn ($notification) => $notification->errorMessage === 'disk unreachable'
    );

    Notification::assertSentOnDemand(
        DatabaseBackupCleanupFailedNotification::class,
        fn ($notification) => $notification->errorMessage === 'disk unreachable'
    );
});
