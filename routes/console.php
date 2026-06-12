<?php

use App\Jobs\CleanupLocalOrderMediaJob;
use App\Jobs\DatabaseBackupJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CleanupLocalOrderMediaJob)->daily();

Schedule::command('recurring-orders:create')
    ->dailyAt('13:00')
    ->timezone('Africa/Johannesburg')
    ->withoutOverlapping()
    ->runInBackground();

if (config('database_backup.schedule_enabled')) {
    Schedule::job(new DatabaseBackupJob)
        ->dailyAt((string) config('database_backup.schedule_time', '03:00'))
        ->withoutOverlapping();
}
