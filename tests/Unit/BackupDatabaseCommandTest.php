<?php

use App\Enums\DatabaseBackupPhase;
use App\Notifications\DatabaseBackupStatusNotification;
use App\Services\DatabaseBackupResult;
use App\Services\DatabaseWasabiBackupService;
use Illuminate\Support\Facades\Notification;

uses(Tests\TestCase::class);

it('sends started and success emails with matching run id when backup completes', function () {
    Notification::fake();

    config([
        'database_backup.notify_emails' => ['ops@example.com'],
        'database_backup.disk' => 'wasabi',
    ]);

    $this->mock(DatabaseWasabiBackupService::class, function ($mock) {
        $mock->shouldReceive('run')->once()->andReturn(
            new DatabaseBackupResult(
                remotePath: 'database-backups/testing/2000-01-01/backup-test-mysql.sql.gz',
                bytesStored: 128,
                connectionName: 'mysql',
            )
        );
    });

    $this->artisan('backup:database')->assertExitCode(0);

    $startedRunId = null;
    $succeededRunId = null;

    Notification::assertSentOnDemand(
        DatabaseBackupStatusNotification::class,
        function ($notification) use (&$startedRunId) {
            if ($notification->phase === DatabaseBackupPhase::Started) {
                $startedRunId = $notification->runId;

                return true;
            }

            return false;
        }
    );

    Notification::assertSentOnDemand(
        DatabaseBackupStatusNotification::class,
        function ($notification) use (&$succeededRunId) {
            if ($notification->phase === DatabaseBackupPhase::Succeeded) {
                $succeededRunId = $notification->runId;

                return true;
            }

            return false;
        }
    );

    expect($startedRunId)->not->toBeNull()
        ->and($succeededRunId)->toBe($startedRunId);
});

it('sends failure email when the backup service throws', function () {
    Notification::fake();

    config([
        'database_backup.notify_emails' => ['ops@example.com'],
    ]);

    $this->mock(DatabaseWasabiBackupService::class, function ($mock) {
        $mock->shouldReceive('run')->once()->andThrow(new RuntimeException('planned failure'));
    });

    $this->artisan('backup:database')->assertExitCode(1);

    Notification::assertSentOnDemand(
        DatabaseBackupStatusNotification::class,
        fn ($notification) => $notification->phase === DatabaseBackupPhase::Started
    );

    Notification::assertSentOnDemand(
        DatabaseBackupStatusNotification::class,
        fn ($notification) => $notification->phase === DatabaseBackupPhase::Failed
            && str_contains((string) $notification->errorMessage, 'planned failure')
    );
});

it('skips mail when --no-notify is passed', function () {
    Notification::fake();

    config([
        'database_backup.notify_emails' => ['ops@example.com'],
        'database_backup.disk' => 'wasabi',
    ]);

    $this->mock(DatabaseWasabiBackupService::class, function ($mock) {
        $mock->shouldReceive('run')->once()->andReturn(
            new DatabaseBackupResult(
                remotePath: 'database-backups/testing/2000-01-01/backup-test-mysql.sql.gz',
                bytesStored: 1,
                connectionName: 'mysql',
            )
        );
    });

    $this->artisan('backup:database', ['--no-notify' => true])->assertExitCode(0);

    Notification::assertNothingSent();
});
