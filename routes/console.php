<?php

use App\Jobs\CleanupLocalOrderMediaJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CleanupLocalOrderMediaJob)->daily();

if (config('database_backup.schedule_enabled')) {
    Schedule::command('backup:database')
        ->dailyAt((string) config('database_backup.schedule_time', '03:00'));
}
