<?php

use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

function touchWasabiBackup(string $key, int $timestamp): void
{
    Storage::disk('wasabi')->put($key, 'gzipped');
    touch(Storage::disk('wasabi')->path($key), $timestamp);
}

beforeEach(function () {
    Storage::fake('wasabi');

    config([
        'database_backup.disk' => 'wasabi',
        'database_backup.path_prefix' => 'database-backups',
        'database_backup.retention_days' => 7,
    ]);
});

it('deletes backups older than the retention window and keeps recent ones', function () {
    $old = 'database-backups/local/2000-01-01/backup-old.sql.gz';
    $recent = 'database-backups/local/2099-01-01/backup-recent.sql.gz';

    touchWasabiBackup($old, now()->subDays(10)->getTimestamp());
    touchWasabiBackup($recent, now()->subDays(1)->getTimestamp());

    $this->artisan('backup:cleanup')
        ->assertSuccessful()
        ->expectsOutputToContain('Deleted 1 backup(s) older than 7 day(s).');

    expect(Storage::disk('wasabi')->exists($old))->toBeFalse()
        ->and(Storage::disk('wasabi')->exists($recent))->toBeTrue();
});

it('does not delete anything when nothing is older than the retention window', function () {
    $recent = 'database-backups/local/2099-01-01/backup-recent.sql.gz';
    touchWasabiBackup($recent, now()->subDays(1)->getTimestamp());

    $this->artisan('backup:cleanup')
        ->assertSuccessful()
        ->expectsOutputToContain('No backups older than 7 day(s) found.');

    expect(Storage::disk('wasabi')->exists($recent))->toBeTrue();
});

it('deletes nothing on a dry run but still reports what would be deleted', function () {
    $old = 'database-backups/local/2000-01-01/backup-old.sql.gz';
    touchWasabiBackup($old, now()->subDays(10)->getTimestamp());

    $this->artisan('backup:cleanup', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Dry run')
        ->expectsOutputToContain($old);

    expect(Storage::disk('wasabi')->exists($old))->toBeTrue();
});

it('honours the --days option over the configured default', function () {
    $key = 'database-backups/local/2000-01-01/backup-old.sql.gz';
    touchWasabiBackup($key, now()->subDays(3)->getTimestamp());

    $this->artisan('backup:cleanup', ['--days' => 2])
        ->assertSuccessful()
        ->expectsOutputToContain('Deleted 1 backup(s) older than 2 day(s).');

    expect(Storage::disk('wasabi')->exists($key))->toBeFalse();
});

it('rejects a retention window below one day', function () {
    $this->artisan('backup:cleanup', ['--days' => 0])
        ->assertFailed();
});
