<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupDatabaseBackupsCommand extends Command
{
    protected $signature = 'backup:cleanup
                            {--disk= : Filesystem disk (default: database_backup.disk, usually wasabi)}
                            {--prefix= : Object key prefix (default: database_backup path_prefix)}
                            {--days= : Retention window in days (default: database_backup.retention_days)}
                            {--dry-run : List what would be deleted without deleting anything}';

    protected $description = 'Delete database backup objects older than the configured retention window';

    public function handle(): int
    {
        $diskName = $this->option('disk') ?: (string) config('database_backup.disk', 'wasabi');
        $prefix = trim((string) ($this->option('prefix') ?: config('database_backup.path_prefix', 'database-backups')), '/');
        $daysOption = $this->option('days');
        $days = (int) ($daysOption !== null && $daysOption !== '' ? $daysOption : config('database_backup.retention_days', 7));
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 1) {
            $this->components->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $cutoffTimestamp = now()->subDays($days)->getTimestamp();

        try {
            $disk = Storage::disk($diskName);
            $paths = $disk->allFiles($prefix);
        } catch (Throwable $e) {
            $this->components->error('Could not list backups: '.$e->getMessage());

            return self::FAILURE;
        }

        $stale = [];
        foreach ($paths as $path) {
            try {
                $lastModified = $disk->lastModified($path);
            } catch (Throwable) {
                continue;
            }

            if ($lastModified > 0 && $lastModified < $cutoffTimestamp) {
                $stale[] = $path;
            }
        }

        if ($stale === []) {
            $this->info("No backups older than {$days} day(s) found.");

            return self::SUCCESS;
        }

        sort($stale, SORT_STRING);

        if ($dryRun) {
            $this->warn('Dry run — '.count($stale)." object(s) older than {$days} day(s) would be deleted:");
            foreach ($stale as $path) {
                $this->line($path);
            }

            return self::SUCCESS;
        }

        $deleted = 0;
        $failed = [];

        foreach ($stale as $path) {
            try {
                if ($disk->delete($path)) {
                    $deleted++;
                } else {
                    $failed[] = $path;
                }
            } catch (Throwable) {
                $failed[] = $path;
            }
        }

        $this->info("Deleted {$deleted} backup(s) older than {$days} day(s).");

        if ($failed !== []) {
            $this->components->error('Failed to delete '.count($failed).' object(s): '.implode(', ', $failed));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
