<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Target disk
    |--------------------------------------------------------------------------
    |
    | S3-compatible disk name (typically "wasabi"). Objects are stored under
    | path_prefix using private visibility from the disk configuration.
    |
    */

    'disk' => env('DATABASE_BACKUP_DISK', 'wasabi'),

    /*
    |--------------------------------------------------------------------------
    | Object key prefix
    |--------------------------------------------------------------------------
    |
    | Full keys look like: {path_prefix}/{app_env}/{Y-m-d}/backup-{uuid}.sql.gz
    |
    */

    'path_prefix' => env('DATABASE_BACKUP_PATH_PREFIX', 'database-backups'),

    /*
    |--------------------------------------------------------------------------
    | Gzip compression
    |--------------------------------------------------------------------------
    |
    | When true, the SQL dump is gzipped before upload. Disable for debugging.
    |
    */

    'compress' => filter_var(env('DATABASE_BACKUP_COMPRESS', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Notification recipients
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of email addresses for lifecycle emails (started,
    | succeeded, failed). Leave empty to skip email (backup still runs).
    |
    */

    'notify_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DATABASE_BACKUP_NOTIFY_EMAILS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | External binaries
    |--------------------------------------------------------------------------
    */

    'mysqldump_binary' => env('DATABASE_BACKUP_MYSQLDUMP_PATH', 'mysqldump'),

    'pg_dump_binary' => env('DATABASE_BACKUP_PG_DUMP_PATH', 'pg_dump'),

    'gzip_binary' => env('DATABASE_BACKUP_GZIP_PATH', 'gzip'),

    /*
    |--------------------------------------------------------------------------
    | Dump process timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'timeout_seconds' => (int) env('DATABASE_BACKUP_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | MySQL / MariaDB dump connection (optional)
    |--------------------------------------------------------------------------
    |
    | By default mysqldump uses the same host/port as your DB_* connection.
    | If mysqldump reports "Unknown MySQL server host 'mysql'", Docker DNS may
    | not resolve the DB service name from the app container. Set these to reach
    | MySQL another way (e.g. DATABASE_BACKUP_MYSQL_HOST=host.docker.internal
    | with DATABASE_BACKUP_MYSQL_PORT matching FORWARD_DB_PORT, often 3306).
    |
    */

    'mysql_dump_host' => env('DATABASE_BACKUP_MYSQL_HOST'),

    'mysql_dump_port' => env('DATABASE_BACKUP_MYSQL_PORT'),

    /*
    |--------------------------------------------------------------------------
    | Docker Compose hostname fallback (mysqldump / pg_dump on the host)
    |--------------------------------------------------------------------------
    |
    | When DB_HOST is "mysql" (or similar) and that name does not resolve — typical
    | when running `php artisan backup:database` on your Mac instead of
    | `sail artisan` — use 127.0.0.1 so the client hits the forwarded DB port.
    | Set to false if you must disable this (e.g. unusual DNS).
    |
    */

    'docker_service_host_fallback' => filter_var(env('DATABASE_BACKUP_DOCKER_HOST_FALLBACK', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | MySQL / MariaDB SSL (optional)
    |--------------------------------------------------------------------------
    |
    | When set, passed to mysqldump as --ssl-ca=...
    |
    */

    'mysql_ssl_ca' => env('MYSQL_ATTR_SSL_CA'),

    /*
    |--------------------------------------------------------------------------
    | Optional schedule (routes/console.php)
    |--------------------------------------------------------------------------
    |
    | When enabled, Laravel schedules DatabaseBackupJob once per day. On Forge,
    | enable the server Scheduler and run a queue worker unless QUEUE_CONNECTION
    | is "sync".
    |
    */

    'schedule_enabled' => filter_var(env('DATABASE_BACKUP_SCHEDULE_ENABLED', false), FILTER_VALIDATE_BOOL),

    'schedule_time' => env('DATABASE_BACKUP_SCHEDULE_TIME', '03:00'),

    /*
    |--------------------------------------------------------------------------
    | Retention (cleanup)
    |--------------------------------------------------------------------------
    |
    | Backup objects older than this many days are deleted by `backup:cleanup`.
    | It runs on the same daily schedule as the backup itself (shortly after,
    | see cleanup_schedule_time) when schedule_enabled is true above.
    |
    */

    'retention_days' => (int) env('DATABASE_BACKUP_RETENTION_DAYS', 7),

    'cleanup_schedule_time' => env('DATABASE_BACKUP_CLEANUP_SCHEDULE_TIME', '03:30'),

];
