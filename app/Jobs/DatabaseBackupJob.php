<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class DatabaseBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3660;

    public function handle(): void
    {
        $exitCode = Artisan::call('backup:database', [
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'backup:database exited with code '.$exitCode.'. '.trim(Artisan::output())
            );
        }
    }
}
