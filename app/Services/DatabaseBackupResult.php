<?php

namespace App\Services;

final class DatabaseBackupResult
{
    public function __construct(
        public readonly string $remotePath,
        public readonly int $bytesStored,
        public readonly string $connectionName,
    ) {}
}
