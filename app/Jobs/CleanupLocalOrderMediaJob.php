<?php

namespace App\Jobs;

use App\Models\Media;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CleanupLocalOrderMediaJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoff = Carbon::now()->subDays(14);

        Media::query()
            ->whereNotNull('local_path')
            ->whereNull('local_deleted_at')
            ->with('mediable')
            ->orderBy('id')
            ->chunkById(200, function ($mediaItems) use ($cutoff) {
                foreach ($mediaItems as $media) {
                    if (! $media->mediable instanceof Order) {
                        continue;
                    }

                    $order = $media->mediable;

                    if (! $media->local_cached_at || $media->local_cached_at->greaterThan($cutoff)) {
                        continue;
                    }

                    $lastRelevantUpdate = $order->updated_at && $order->updated_at->greaterThan($media->created_at)
                        ? $order->updated_at
                        : $media->created_at;

                    if ($lastRelevantUpdate->greaterThan($cutoff)) {
                        continue;
                    }

                    $diskName = $media->local_disk ?: 'local';
                    $disk = Storage::disk($diskName);

                    $disk->delete($media->local_path);

                    $media->forceFill([
                        'local_deleted_at' => now(),
                    ])->save();
                }
            });
    }
}
