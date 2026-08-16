<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CleanupDatabaseBackupsCommand extends Command
{
    protected $signature = 'backup:cleanup
                            {--disk= : Filesystem disk (default: database_backup.disk, usually wasabi)}
                            {--prefix= : Object key prefix (default: database_backup path_prefix)}
                            {--days= : Retention window in days (default: database_backup.retention_days)}
                            {--dry-run : List what would be deleted without deleting anything}
                            {--entire-bucket : Allow operating from the bucket root when the resolved prefix is empty}';

    protected $description = 'Delete database backup objects older than the configured retention window';

    public function handle(): int
    {
        $diskName = $this->option('disk') ?: (string) config('database_backup.disk', 'wasabi');
        $daysOption = $this->option('days');
        $days = (int) ($daysOption !== null && $daysOption !== '' ? $daysOption : config('database_backup.retention_days', 7));
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 1) {
            $this->components->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        try {
            $prefix = $this->resolvePrefix();
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $cutoffTimestamp = now()->subDays($days)->getTimestamp();

        try {
            $disk = Storage::disk($diskName);
            $stale = $this->findStaleObjects($disk, $prefix, $cutoffTimestamp);
        } catch (Throwable $e) {
            $this->components->error('Could not list backups: '.$e->getMessage());

            return self::FAILURE;
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

    /**
     * Resolve the object key prefix, refusing to operate on the entire bucket
     * root unless explicitly allowed — this command deletes objects, and the
     * disk may also hold unrelated data (e.g. migrated order documents).
     */
    private function resolvePrefix(): string
    {
        $prefixOption = $this->option('prefix');

        $prefix = ($prefixOption !== null && $prefixOption !== '')
            ? trim((string) $prefixOption, '/')
            : trim((string) config('database_backup.path_prefix', 'database-backups'), '/');

        if ($prefix === '' && ! $this->option('entire-bucket')) {
            throw new RuntimeException('Resolved prefix is empty, which would operate on the entire bucket root. Pass --entire-bucket to confirm this is intentional, or set a non-empty prefix.');
        }

        return $prefix;
    }

    /**
     * @return list<string>
     */
    private function findStaleObjects(FilesystemAdapter $disk, string $prefix, int $cutoffTimestamp): array
    {
        $stale = [];

        foreach ($disk->getDriver()->listContents($prefix, true) as $attributes) {
            if (! $attributes->isFile()) {
                continue;
            }

            $lastModified = $attributes->lastModified();

            if ($lastModified !== null && $lastModified < $cutoffTimestamp) {
                $stale[] = $attributes->path();
            }
        }

        return $stale;
    }
}
