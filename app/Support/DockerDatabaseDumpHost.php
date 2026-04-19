<?php

namespace App\Support;

/**
 * When Artisan runs on the host (e.g. `php artisan` instead of `sail artisan`), subprocesses
 * such as mysqldump use the machine's DNS — the hostname "mysql" from Docker Compose does not
 * resolve. If resolution fails, fall back to loopback so a published DB port works.
 */
final class DockerDatabaseDumpHost
{
    public static function resolve(string $host, string $driver, bool $fallbackEnabled = true): string
    {
        if (! $fallbackEnabled) {
            return $host;
        }

        $composeServiceNames = match ($driver) {
            'mysql', 'mariadb' => ['mysql', 'mariadb'],
            'pgsql' => ['pgsql', 'postgres'],
            default => [],
        };

        if (! in_array($host, $composeServiceNames, true)) {
            return $host;
        }

        $resolved = @gethostbyname($host);

        if ($resolved !== $host) {
            return $host;
        }

        return '127.0.0.1';
    }
}
