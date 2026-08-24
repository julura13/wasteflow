<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateOrderDocumentsToWasabiCommand extends Command
{
    protected $signature = 'media:migrate-order-documents-to-wasabi
                            {--dry-run : List candidates and make no changes}
                            {--limit= : Maximum number of media rows to process}';

    protected $description = 'Copy local-only supporting documents (orders in documents_required or finalized) to Wasabi, retain the local file as a short-term cache, and point media.disk at Wasabi.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limitOption = $this->option('limit');
        $limit = is_string($limitOption) && $limitOption !== '' ? (int) $limitOption : null;
        if ($limit !== null && $limit < 1) {
            $this->components->error('If set, --limit must be a positive integer.');

            return self::FAILURE;
        }

        $query = $this->baseQuery();
        if ($limit !== null) {
            $query->limit($limit);
        }

        $candidates = $query->with('mediable')->get();
        if ($candidates->isEmpty()) {
            $this->components->info('No local supporting documents need migration for qualifying orders.');

            return self::SUCCESS;
        }

        $this->line('Found <info>'.$candidates->count().'</info> file(s) to migrate'.($dryRun ? ' (dry run).' : '.'));

        $ok = 0;
        $failed = 0;

        foreach ($candidates as $media) {
            $result = $this->processOne($media, $dryRun);
            if ($result) {
                $ok++;
            } else {
                $failed++;
            }
        }

        $this->newLine();
        if ($failed === 0) {
            $this->components->info("Completed: {$ok} processed.");

            return self::SUCCESS;
        }

        $this->components->warn("Finished with {$failed} failure(s) and {$ok} success(es).");

        return self::FAILURE;
    }

    /**
     * @return Builder<Media>
     */
    private function baseQuery(): Builder
    {
        return Media::query()
            ->where([
                'mediable_type' => Order::class,
                'collection' => 'supporting_documents',
                'disk' => 'local',
            ])
            ->whereNull('local_deleted_at')
            ->whereHas('mediable', function (Builder $q): void {
                $q->whereIn('status', ['documents_required', 'finalized']);
            })
            ->orderBy('id');
    }

    private function processOne(Media $media, bool $dryRun): bool
    {
        $mediable = $media->mediable;
        if (! $mediable instanceof Order) {
            $this->line("  [skip] Media {$media->id}: mediable is not an order.");

            return false;
        }

        $sourcePath = $media->path;
        if ($sourcePath === '') {
            $this->line("  [skip] Media {$media->id}: empty path.");

            return false;
        }

        $local = Storage::disk('local');
        if (! $local->exists($sourcePath)) {
            $this->line("  <fg=red>[error]</> Media {$media->id}: missing on local disk: {$sourcePath}");

            return false;
        }

        $wasabi = Storage::disk('wasabi');

        try {
            if ($wasabi->exists($sourcePath)) {
                if ($this->fileSizesMatch($local, $wasabi, $sourcePath)) {
                    if (! $dryRun) {
                        DB::transaction(function () use ($media, $sourcePath): void {
                            $this->switchMediaToWasabiWithLocalCache($media, $sourcePath);
                        });
                        $this->line("  <fg=green>[ok]</> Media {$media->id} → row updated (object already in Wasabi). (<fg=gray>{$sourcePath}</>)");
                    } else {
                        $this->line("  [dry-run] Media {$media->id}: Wasabi key exists with matching size; would update row only. (<fg=gray>{$sourcePath}</>)");
                    }

                    return true;
                }
                $this->line("  <fg=red>[error]</> Media {$media->id}: Wasabi has {$sourcePath} with different size; resolve manually.");

                return false;
            }
        } catch (Throwable $e) {
            $this->line("  <fg=red>[error]</> Media {$media->id}: cannot read Wasabi: ".$e->getMessage());

            return false;
        }

        if ($dryRun) {
            $this->line("  [dry-run] Media {$media->id}: would upload to Wasabi. (<fg=gray>{$sourcePath}</>)");

            return true;
        }

        $contents = $local->get($sourcePath);
        if (! is_string($contents)) {
            $this->line("  <fg=red>[error]</> Media {$media->id}: could not read local file: {$sourcePath}");

            return false;
        }

        try {
            $wasabi->put($sourcePath, $contents);
        } catch (Throwable $e) {
            $this->line("  <fg=red>[error]</> Media {$media->id}: Wasabi put failed: ".$e->getMessage());

            return false;
        }

        if (! $wasabi->exists($sourcePath)) {
            $this->line("  <fg=red>[error]</> Media {$media->id}: upload reported success but key missing: {$sourcePath}");

            return false;
        }

        try {
            DB::transaction(function () use ($media, $sourcePath): void {
                $this->switchMediaToWasabiWithLocalCache($media, $sourcePath);
            });
        } catch (Throwable $e) {
            $this->line("  <fg=red>[error]</> Media {$media->id}: database update failed: ".$e->getMessage());

            return false;
        }

        $this->line("  <fg=green>[ok]</> Media {$media->id} → Wasabi, local cache kept. (<fg=gray>{$sourcePath}</>)");

        return true;
    }

    private function fileSizesMatch(\Illuminate\Contracts\Filesystem\Filesystem $local, \Illuminate\Contracts\Filesystem\Filesystem $remote, string $path): bool
    {
        return $local->size($path) === $remote->size($path);
    }

    private function switchMediaToWasabiWithLocalCache(Media $media, string $sourcePath): void
    {
        $media->forceFill([
            'disk' => 'wasabi',
            'local_disk' => 'local',
            'local_path' => $sourcePath,
            'local_cached_at' => now(),
        ])->save();
    }
}
