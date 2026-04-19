<?php

use App\Jobs\DatabaseBackupJob;
use Illuminate\Support\Facades\Artisan;

uses(Tests\TestCase::class);

it('delegates to the backup:database artisan command', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:database', ['--no-interaction' => true])
        ->andReturn(0);

    (new DatabaseBackupJob)->handle();
});

it('throws when the command exits unsuccessfully', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('backup:database', ['--no-interaction' => true])
        ->andReturn(1);

    Artisan::shouldReceive('output')->andReturn('error output');

    (new DatabaseBackupJob)->handle();
})->throws(\RuntimeException::class);
