<?php

use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

it('lists objects under the backup prefix on the configured disk', function () {
    Storage::fake('wasabi');
    Storage::disk('wasabi')->put('database-backups/local/2000-01-01/backup-test.sql.gz', 'gzipped');

    config([
        'database_backup.disk' => 'wasabi',
        'database_backup.path_prefix' => 'database-backups',
    ]);

    $this->artisan('backup:list', ['--keys-only' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('database-backups/local/2000-01-01/backup-test.sql.gz');
});
