<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WasabiListBucketsCommand extends Command
{
    protected $signature = 'wasabi:buckets
                            {--disk=wasabi : S3/Laravel disk whose endpoint and credentials are used (ListBuckets + ListObjectsV2)}
                            {--bucket= : Only list objects in this bucket (skips ListBuckets; use for a quick single-bucket check)}
                            {--prefix= : Object key prefix when listing objects (trimmed, no leading slash)}
                            {--limit=200 : Max objects to show per bucket (1–10000)}
                            {--keys-only : List object keys only (no size or last modified)}
                            {--buckets-only : List bucket names only; do not list objects}';

    protected $description = 'List S3/Wasabi buckets visible to the app credentials, then list object keys in each (or one bucket) to verify uploads landed';

    public function handle(): int
    {
        $diskName = (string) $this->option('disk');
        $singleBucket = $this->option('bucket');
        $prefixOption = $this->option('prefix');
        $prefix = $prefixOption !== null && $prefixOption !== '' ? ltrim((string) $prefixOption, '/') : '';
        $limit = max(1, min(10000, (int) $this->option('limit')));
        $keysOnly = (bool) $this->option('keys-only');
        $bucketsOnly = (bool) $this->option('buckets-only');

        $configBucket = (string) (config("filesystems.disks.{$diskName}.bucket") ?? '');

        try {
            $disk = Storage::disk($diskName);
        } catch (Throwable $e) {
            $this->components->error('Could not resolve disk: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $disk instanceof AwsS3V3Adapter) {
            $this->components->error("Disk [{$diskName}] is not S3 (expected driver s3 with credentials). Use e.g. --disk=wasabi.");

            return self::FAILURE;
        }

        /** @var S3Client $client */
        $client = $disk->getClient();
        $this->line("Using S3 client from disk: <info>{$diskName}</info>");
        if ($configBucket !== '') {
            $this->line("Configured primary bucket (env for this disk): <info>{$configBucket}</info>");
        }
        $this->newLine();

        if ($bucketsOnly && $singleBucket) {
            $this->components->error('Use either --buckets-only or --bucket=, not both.');

            return self::FAILURE;
        }

        if ($singleBucket) {
            return $this->listObjectsForBucket($client, $singleBucket, $prefix, $limit, $keysOnly);
        }

        try {
            $list = $client->listBuckets();
        } catch (Throwable $e) {
            $this->components->error('ListBuckets failed: '.$e->getMessage());
            $this->line('The IAM/user key may lack s3:ListAllMyBuckets, or the endpoint/region is wrong.');

            return self::FAILURE;
        }

        $buckets = $list['Buckets'] ?? [];
        usort($buckets, fn (array $a, array $b) => strcmp((string) ($a['Name'] ?? ''), (string) ($b['Name'] ?? '')));

        if ($buckets === []) {
            $this->warn('ListBuckets returned no buckets for this account.');

            return self::SUCCESS;
        }

        $anyListFailed = false;
        foreach ($buckets as $bucket) {
            $name = (string) ($bucket['Name'] ?? '');
            if ($name === '') {
                continue;
            }
            $this->line("<fg=cyan>Bucket:</> <info>{$name}</info>");
            $created = $bucket['CreationDate'] ?? null;
            if ($created instanceof \DateTimeInterface) {
                $this->line('  Created: '.$created->format('Y-m-d H:i:s').' UTC');
            } elseif ($created) {
                $this->line('  Created: '.gmdate('Y-m-d H:i:s', strtotime((string) $created)).' UTC');
            }
            if ($name === $configBucket) {
                $this->line("  <fg=gray>(this is the app disk's configured bucket)</>");
            }
            if ($bucketsOnly) {
                $this->newLine();

                continue;
            }
            if (! $this->listObjectsIntoOutput($client, $name, $prefix, $limit, $keysOnly)) {
                $anyListFailed = true;
            }
        }

        if ($bucketsOnly) {
            $this->info('Listed '.count($buckets).' bucket name(s) (--buckets-only).');
        }

        return $anyListFailed ? self::FAILURE : self::SUCCESS;
    }

    private function listObjectsForBucket(S3Client $client, string $bucket, string $prefix, int $limit, bool $keysOnly): int
    {
        $this->line("Bucket: <info>{$bucket}</info>");

        return $this->listObjectsIntoOutput($client, $bucket, $prefix, $limit, $keysOnly) ? self::SUCCESS : self::FAILURE;
    }

    private function listObjectsIntoOutput(S3Client $client, string $bucket, string $prefix, int $limit, bool $keysOnly): bool
    {
        $this->line('  Prefix: <info>'.($prefix === '' ? '(none)' : $prefix).'</info>');
        $started = microtime(true);

        try {
            $objects = $this->collectObjects($client, $bucket, $prefix, $limit);
        } catch (Throwable $e) {
            $this->components->error('  listObjectsV2 failed: '.$e->getMessage());
            $this->newLine();

            return false;
        }
        usort($objects, fn (array $a, array $b) => strcmp($b['key'], $a['key']));
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        if ($objects === []) {
            $this->warn('  No objects in this result set (empty prefix, or no keys yet).');
            $this->line("  (request took {$elapsedMs} ms)");
            $this->newLine();

            return true;
        }

        if ($keysOnly) {
            foreach ($objects as $row) {
                $this->line('  '.$row['key']);
            }
        } else {
            $rows = [];
            foreach ($objects as $row) {
                $lastMod = $row['lastModified'];
                $rows[] = [
                    $row['key'],
                    $this->formatBytes($row['size']),
                    $lastMod ? $lastMod->format('Y-m-d H:i:s').' UTC' : '—',
                ];
            }
            $this->table(['Key', 'Size', 'Last modified (UTC)'], $rows);
        }

        $this->line('  <fg=gray>Listed '.count($objects)." object(s) in {$elapsedMs} ms (limit {$limit}).</>");
        $this->newLine();

        return true;
    }

    /**
     * @return list<array{key: string, size: int, lastModified: ?\DateTimeInterface}>
     */
    private function collectObjects(S3Client $client, string $bucket, string $prefix, int $limit): array
    {
        $objects = [];
        $params = [
            'Bucket' => $bucket,
            'Prefix' => $prefix,
            'MaxKeys' => min(1000, $limit),
        ];
        $token = null;

        do {
            if ($token !== null) {
                $params['ContinuationToken'] = $token;
            }

            $result = $client->listObjectsV2($params);
            $contents = $result['Contents'] ?? [];
            foreach ($contents as $object) {
                $key = (string) ($object['Key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $lm = $object['LastModified'] ?? null;
                $lastModified = $lm instanceof \DateTimeInterface ? $lm : null;
                $objects[] = [
                    'key' => $key,
                    'size' => (int) ($object['Size'] ?? 0),
                    'lastModified' => $lastModified,
                ];
                if (count($objects) >= $limit) {
                    return $objects;
                }
            }

            if (! ($result['IsTruncated'] ?? false)) {
                break;
            }
            $token = $result['NextContinuationToken'] ?? null;
            if (! $token) {
                break;
            }
        } while (count($objects) < $limit);

        return $objects;
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
