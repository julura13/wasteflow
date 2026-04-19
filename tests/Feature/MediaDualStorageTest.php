<?php

use App\Models\Media;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

it('stores supporting documents to wasabi and also caches a local copy during finalisation', function () {
    Storage::fake('local');
    Storage::fake('wasabi');

    $permission = Permission::findOrCreate('orders-capture-documents');
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    $order = Order::query()->create([
        'company_id' => 1,
        'created_by' => $user->id,
        'order_type' => 'waste',
        'status' => 'documents_required',
        'requested_collection_date' => now()->toDateString(),
    ]);

    $file = UploadedFile::fake()->create('supporting.pdf', 100, 'application/pdf');

    $response = $this
        ->actingAs($user)
        ->post(route('media.upload'), [
            'file' => $file,
            'mediable_type' => Order::class,
            'mediable_id' => $order->id,
            'collection' => 'supporting_documents',
            'description' => 'Test upload',
        ]);

    $response->assertRedirect();

    $media = Media::query()->latest('id')->firstOrFail();

    expect($media->disk)->toBe('wasabi')
        ->and($media->collection)->toBe('supporting_documents')
        ->and($media->local_disk)->toBe('local')
        ->and($media->local_path)->not->toBeNull();

    Storage::disk('wasabi')->assertExists($media->path);
    Storage::disk('local')->assertExists($media->local_path);
});
