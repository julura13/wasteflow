<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListDatabaseBackupsCommand extends Command
{
    protected $signature = 'backup:list
                            {--disk= : Filesystem disk (default: database_backup.disk, usually wasabi)}
                            {--prefix= : Object key prefix (default: database_backup path_prefix)}
                            {--limit=200 : Maximum objects to list}
                            {--keys-only : List keys only (faster; skips per-object size and timestamps)}
                            {--entire-bucket : Ignore the default prefix and list from bucket root (still limited by --limit)}';

    protected $description = 'List database backup objects in S3-compatible storage (e.g. Wasabi) for connectivity checks';

    public function handle(): int
    {
        $diskName = $this->option('disk') ?: (string) config('database_backup.disk', 'wasabi');

        $prefixOption = $this->option('prefix');
        if ($this->option('entire-bucket')) {
            $prefix = '';
        } elseif ($prefixOption !== null && $prefixOption !== '') {
            $prefix = trim((string) $prefixOption, '/');
        } else {
            $prefix = trim((string) config('database_backup.path_prefix', 'database-backups'), '/');
        }

        $limit = max(1, min(5000, (int) $this->option('limit')));
        $keysOnly = (bool) $this->option('keys-only');

        $this->line("Disk: <info>{$diskName}</info>");
        $this->line('Prefix: <info>'.($prefix === '' ? '(bucket root)' : $prefix).'</info>');
        $this->newLine();

        $started = microtime(true);

        try {
            $disk = Storage::disk($diskName);
        } catch (Throwable $e) {
            $this->components->error('Could not resolve disk: '.$e->getMessage());

            return self::FAILURE;
        }

        try {
            $paths = $prefix === ''
                ? $disk->allFiles()
                : $disk->allFiles($prefix);
        } catch (Throwable $e) {
            $this->components->error('Listing failed: '.$e->getMessage());
            $this->line('Check WASABI_* / filesystems config, network, region, endpoint, and bucket name.');

            return self::FAILURE;
        }

        rsort($paths, SORT_STRING);
        $paths = array_slice($paths, 0, $limit);

        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        if ($paths === []) {
            $this->warn('No objects found for this prefix.');
            $this->line("Request took {$elapsedMs} ms.");

            return self::SUCCESS;
        }

        if ($keysOnly) {
            foreach ($paths as $path) {
                $this->line($path);
            }
        } else {
            $rows = [];
            foreach ($paths as $path) {
                try {
                    $size = $disk->size($path);
                    $lastMod = $disk->lastModified($path);
                } catch (Throwable) {
                    $size = 0;
                    $lastMod = 0;
                }

                $rows[] = [
                    $path,
                    $this->formatBytes($size),
                    $lastMod > 0 ? gmdate('Y-m-d H:i:s', $lastMod).' UTC' : '—',
                ];
            }

            $this->table(['Key', 'Size', 'Last modified (UTC)'], $rows);
        }

        $this->newLine();
        $this->info('Listed '.count($paths).' object(s) in '.$elapsedMs.' ms (limit '.$limit.').');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $i = min((int) floor(log($bytes, 1024)) - 1, count($units) - 1);
        $i = max(0, $i);

        return number_format($bytes / (1024 ** ($i + 1)), 2).' '.$units[$i];
    }
}
