<?php

use App\Models\Media;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

it('migrates local supporting documents to wasabi and keeps the local file as cache metadata', function () {
    Storage::fake('local');
    Storage::fake('wasabi');

    $user = User::factory()->create();

    $order = Order::query()->create([
        'company_id' => 1,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => now()->toDateString(),
    ]);

    $path = 'orders/'.$order->id.'/supporting_documents/legacy.pdf';
    Storage::disk('local')->put($path, 'legacy-bytes');

    $media = Media::query()->create([
        'mediable_type' => Order::class,
        'mediable_id' => $order->id,
        'file_name' => 'legacy.pdf',
        'original_name' => 'legacy.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
        'path' => $path,
        'file_size' => 12,
        'collection' => 'supporting_documents',
        'description' => null,
    ]);

    $exit = Artisan::call('media:migrate-order-documents-to-wasabi');

    expect($exit)->toBe(0);

    $media->refresh();
    expect($media->disk)->toBe('wasabi')
        ->and($media->local_disk)->toBe('local')
        ->and($media->local_path)->toBe($path)
        ->and($media->local_cached_at)->not->toBeNull()
        ->and($media->local_deleted_at)->toBeNull();

    Storage::disk('wasabi')->assertExists($path);
    Storage::disk('local')->assertExists($path);
});

it('skips media for orders that are not in the finalization statuses', function () {
    Storage::fake('local');
    Storage::fake('wasabi');

    $user = User::factory()->create();

    $order = Order::query()->create([
        'company_id' => 1,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'pending',
        'requested_collection_date' => now()->toDateString(),
    ]);

    $path = 'orders/'.$order->id.'/supporting_documents/odd.pdf';
    Storage::disk('local')->put($path, 'x');

    $media = Media::query()->create([
        'mediable_type' => Order::class,
        'mediable_id' => $order->id,
        'file_name' => 'odd.pdf',
        'original_name' => 'odd.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
        'path' => $path,
        'file_size' => 1,
        'collection' => 'supporting_documents',
        'description' => null,
    ]);

    $exit = Artisan::call('media:migrate-order-documents-to-wasabi');

    expect($exit)->toBe(0);

    $media->refresh();
    expect($media->disk)->toBe('local');
    Storage::disk('wasabi')->assertMissing($path);
});

it('dry run does not upload or change the media row', function () {
    Storage::fake('local');
    Storage::fake('wasabi');

    $user = User::factory()->create();

    $order = Order::query()->create([
        'company_id' => 1,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'documents_required',
        'requested_collection_date' => now()->toDateString(),
    ]);

    $path = 'orders/'.$order->id.'/supporting_documents/dry.pdf';
    Storage::disk('local')->put($path, 'dry');

    $media = Media::query()->create([
        'mediable_type' => Order::class,
        'mediable_id' => $order->id,
        'file_name' => 'dry.pdf',
        'original_name' => 'dry.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
        'path' => $path,
        'file_size' => 3,
        'collection' => 'supporting_documents',
        'description' => null,
    ]);

    Artisan::call('media:migrate-order-documents-to-wasabi', ['--dry-run' => true]);

    $media->refresh();
    expect($media->disk)->toBe('local');
    Storage::disk('wasabi')->assertMissing($path);
});

it('reconciles a row when wasabi already has the key after a failed database update', function () {
    Storage::fake('local');
    Storage::fake('wasabi');

    $user = User::factory()->create();

    $order = Order::query()->create([
        'company_id' => 1,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'finalized',
        'requested_collection_date' => now()->toDateString(),
    ]);

    $path = 'orders/'.$order->id.'/supporting_documents/orphan.pdf';
    Storage::disk('local')->put($path, 'orphan');
    Storage::disk('wasabi')->put($path, 'orphan');

    $media = Media::query()->create([
        'mediable_type' => Order::class,
        'mediable_id' => $order->id,
        'file_name' => 'orphan.pdf',
        'original_name' => 'orphan.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
        'path' => $path,
        'file_size' => 6,
        'collection' => 'supporting_documents',
        'description' => null,
    ]);

    $exit = Artisan::call('media:migrate-order-documents-to-wasabi');

    expect($exit)->toBe(0);

    $media->refresh();
    expect($media->disk)->toBe('wasabi');
});
