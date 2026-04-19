<?php

namespace App\Services;

use App\Support\DockerDatabaseDumpHost;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;

class DatabaseWasabiBackupService
{
    public function run(string $connectionName, string $runId): DatabaseBackupResult
    {
        $config = config('database.connections.'.$connectionName);
        if ($config === null) {
            throw new RuntimeException("Unknown database connection [{$connectionName}].");
        }

        $driver = $config['driver'];

        $tempDir = storage_path('app/temp/database-backups');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $sqlPath = $tempDir.'/dump-'.$runId.'.sql';
        $uploadPath = $sqlPath;
        $cleanup = [$sqlPath];

        try {
            match ($driver) {
                'mysql', 'mariadb' => $this->dumpMysql($config, $sqlPath),
                'pgsql' => $this->dumpPgsql($config, $sqlPath),
                'sqlite' => $this->dumpSqlite($config, $sqlPath),
                default => throw new RuntimeException("Unsupported database driver [{$driver}] for backup. Supported: mysql, mariadb, pgsql, sqlite."),
            };

            if (config('database_backup.compress', true)) {
                $uploadPath = $this->gzipFile($sqlPath);
                $cleanup[] = $uploadPath;
            }

            $remotePath = $this->buildRemoteKey(
                $connectionName,
                $runId,
                str_ends_with($uploadPath, '.gz')
            );

            $bytes = filesize($uploadPath);
            if ($bytes === false) {
                throw new RuntimeException('Could not read backup file size.');
            }

            $stream = fopen($uploadPath, 'r');
            if ($stream === false) {
                throw new RuntimeException('Could not open backup file for reading.');
            }

            try {
                $disk = (string) config('database_backup.disk', 'wasabi');
                $ok = Storage::disk($disk)->writeStream($remotePath, $stream);
                if (! $ok) {
                    throw new RuntimeException("Failed to upload backup to disk [{$disk}].");
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return new DatabaseBackupResult(
                remotePath: $remotePath,
                bytesStored: (int) $bytes,
                connectionName: $connectionName,
            );
        } finally {
            foreach (array_unique($cleanup) as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    private function buildRemoteKey(string $connectionName, string $runId, bool $gzipped): string
    {
        $prefix = trim((string) config('database_backup.path_prefix', 'database-backups'), '/');
        $env = config('app.env', 'production');
        $date = now()->format('Y-m-d');
        $ext = $gzipped ? '.sql.gz' : '.sql';
        $safeConnection = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $connectionName) ?? $connectionName;

        return $prefix.'/'.$env.'/'.$date.'/backup-'.$runId.'-'.$safeConnection.$ext;
    }

    private function gzipFile(string $sqlPath): string
    {
        $gzip = (string) config('database_backup.gzip_binary', 'gzip');
        $gzPath = $sqlPath.'.gz';

        $result = Process::timeout((int) config('database_backup.timeout_seconds', 3600))
            ->run([$gzip, '-f', $sqlPath]);

        if (! $result->successful()) {
            throw new RuntimeException('gzip failed: '.Str::limit(trim($result->errorOutput().$result->output()), 2000));
        }

        if (! is_file($gzPath)) {
            throw new RuntimeException('gzip did not produce an output file.');
        }

        return $gzPath;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dumpMysql(array $config, string $sqlPath): void
    {
        $binary = (string) config('database_backup.mysqldump_binary', 'mysqldump');
        $args = [
            $binary,
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--set-charset',
            '--default-character-set='.($config['charset'] ?? 'utf8mb4'),
        ];

        if (! empty($config['unix_socket'])) {
            $args[] = '--socket='.$config['unix_socket'];
        } else {
            $explicitHost = config('database_backup.mysql_dump_host');
            $host = (is_string($explicitHost) && $explicitHost !== '')
                ? $explicitHost
                : (string) ($config['host'] ?? '127.0.0.1');

            $explicitPort = config('database_backup.mysql_dump_port');
            $port = ($explicitPort !== null && $explicitPort !== '')
                ? (int) $explicitPort
                : (int) ($config['port'] ?? 3306);

            $driver = (string) ($config['driver'] ?? 'mysql');
            $host = DockerDatabaseDumpHost::resolve(
                $host,
                $driver,
                (bool) config('database_backup.docker_service_host_fallback', true)
            );

            $args[] = '-h'.$host;
            $args[] = '-P'.(string) $port;
        }

        $args[] = '-u'.$config['username'];
        $args[] = '--password='.($config['password'] ?? '');
        $args[] = $config['database'];

        $options = $config['options'] ?? [];
        if (is_array($options) && isset($options[PDO::MYSQL_ATTR_SSL_CA]) && $options[PDO::MYSQL_ATTR_SSL_CA]) {
            $args[] = '--ssl-ca='.$options[PDO::MYSQL_ATTR_SSL_CA];
        }

        $this->runShellRedirectStdout($args, $sqlPath);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dumpPgsql(array $config, string $sqlPath): void
    {
        $binary = (string) config('database_backup.pg_dump_binary', 'pg_dump');
        $pgHost = (string) $config['host'];
        $pgHost = DockerDatabaseDumpHost::resolve(
            $pgHost,
            'pgsql',
            (bool) config('database_backup.docker_service_host_fallback', true)
        );

        $args = [
            $binary,
            '-h', $pgHost,
            '-p', (string) ($config['port'] ?? 5432),
            '-U', $config['username'],
            '-d', $config['database'],
            '-F', 'p',
        ];

        $password = (string) ($config['password'] ?? '');

        $command = $this->escapeShellArgs($args).' > '.escapeshellarg($sqlPath);

        $result = Process::timeout((int) config('database_backup.timeout_seconds', 3600))
            ->env(['PGPASSWORD' => $password])
            ->command($command)
            ->run();

        if (! $result->successful()) {
            throw new RuntimeException('pg_dump failed: '.Str::limit(trim($result->errorOutput()), 2000));
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dumpSqlite(array $config, string $sqlPath): void
    {
        $databasePath = (string) ($config['database'] ?? '');
        if ($databasePath === ':memory:' || $databasePath === '') {
            throw new RuntimeException('Cannot backup an in-memory or empty SQLite database path.');
        }

        $resolved = $databasePath;
        if (! is_file($resolved)) {
            $candidate = base_path($databasePath);
            if (is_file($candidate)) {
                $resolved = $candidate;
            }
        }

        if (! is_file($resolved)) {
            throw new RuntimeException('SQLite database file was not found at '.$databasePath.'.');
        }

        $result = Process::timeout((int) config('database_backup.timeout_seconds', 3600))
            ->run(['sqlite3', $resolved, '.dump']);

        if (! $result->successful()) {
            throw new RuntimeException('sqlite3 .dump failed: '.Str::limit(trim($result->errorOutput().$result->output()), 2000));
        }

        if (file_put_contents($sqlPath, $result->output()) === false) {
            throw new RuntimeException('Could not write SQLite dump file.');
        }
    }

    /**
     * @param  list<string>  $args
     */
    private function runShellRedirectStdout(array $args, string $sqlPath): void
    {
        $command = $this->escapeShellArgs($args).' > '.escapeshellarg($sqlPath);

        $result = Process::timeout((int) config('database_backup.timeout_seconds', 3600))
            ->command($command)
            ->run();

        if (! $result->successful()) {
            throw new RuntimeException('mysqldump failed: '.Str::limit(trim($result->errorOutput()), 2000));
        }

        if (! is_file($sqlPath) || filesize($sqlPath) === 0) {
            throw new RuntimeException('mysqldump produced no output.');
        }
    }

    /**
     * @param  list<string>  $args
     */
    private function escapeShellArgs(array $args): string
    {
        return implode(' ', array_map('escapeshellarg', $args));
    }
}
