<?php

use App\Jobs\CleanupLocalOrderMediaJob;
use App\Models\Media;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

it('deletes only the local cached file after 14 days of no order updates', function () {
    Storage::fake('local');

    Carbon::setTestNow(Carbon::parse('2026-04-09 12:00:00'));

    $user = User::factory()->create();

    $order = Order::query()->create([
        'company_id' => 1,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'documents_required',
        'requested_collection_date' => now()->toDateString(),
    ]);
    $order->forceFill([
        'created_at' => now()->subDays(30),
        'updated_at' => now()->subDays(20),
    ])->saveQuietly();

    $localPath = 'orders/'.$order->id.'/supporting_documents/local.pdf';
    Storage::disk('local')->put($localPath, 'local-bytes');

    $media = Media::query()->create([
        'mediable_type' => Order::class,
        'mediable_id' => $order->id,
        'file_name' => 'local.pdf',
        'original_name' => 'local.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'wasabi',
        'path' => 'orders/'.$order->id.'/supporting_documents/wasabi.pdf',
        'local_disk' => 'local',
        'local_path' => $localPath,
        'local_cached_at' => now()->subDays(20),
        'file_size' => 123,
        'collection' => 'supporting_documents',
        'description' => null,
    ]);
    $media->forceFill([
        'created_at' => now()->subDays(20),
        'updated_at' => now()->subDays(20),
    ])->saveQuietly();

    $media->refresh();
    expect($media->local_cached_at)->not->toBeNull();

    (new CleanupLocalOrderMediaJob)->handle();

    $media->refresh();

    expect($media->local_deleted_at)->not->toBeNull();
    Storage::disk('local')->assertMissing($localPath);
});

it('keeps the local cached file if the order has been updated recently', function () {
    Storage::fake('local');

    Carbon::setTestNow(Carbon::parse('2026-04-09 12:00:00'));

    $user = User::factory()->create();

    $order = Order::query()->create([
        'company_id' => 1,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'documents_required',
        'requested_collection_date' => now()->toDateString(),
    ]);
    $order->forceFill([
        'created_at' => now()->subDays(30),
        'updated_at' => now()->subDays(2),
    ])->saveQuietly();

    $localPath = 'orders/'.$order->id.'/supporting_documents/local.pdf';
    Storage::disk('local')->put($localPath, 'local-bytes');

    $media = Media::query()->create([
        'mediable_type' => Order::class,
        'mediable_id' => $order->id,
        'file_name' => 'local.pdf',
        'original_name' => 'local.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'wasabi',
        'path' => 'orders/'.$order->id.'/supporting_documents/wasabi.pdf',
        'local_disk' => 'local',
        'local_path' => $localPath,
        'local_cached_at' => now()->subDays(20),
        'file_size' => 123,
        'collection' => 'supporting_documents',
        'description' => null,
    ]);
    $media->forceFill([
        'created_at' => now()->subDays(20),
        'updated_at' => now()->subDays(20),
    ])->saveQuietly();

    (new CleanupLocalOrderMediaJob)->handle();

    $media->refresh();

    Storage::disk('local')->assertExists($localPath);
    expect($media->local_deleted_at)->toBeNull();
});
