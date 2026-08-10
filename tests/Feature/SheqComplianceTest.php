<?php

use App\Models\ContentView;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

it('allows admins to upload a SHEQ compliance document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post('/sheq-compliance', [
            'title' => 'HSE Policy',
            'description' => 'Annual HSE policy document.',
            'file' => UploadedFile::fake()->create('hse-policy.pdf', 100),
        ])
        ->assertRedirect();

    $document = Media::where('collection', 'sheq_compliance')->first();
    expect($document)->not->toBeNull();
    expect($document->title)->toBe('HSE Policy');
    expect($document->uploaded_by)->toBe($admin->id);
    expect($document->mediable_type)->toBeNull();
    expect($document->mediable_id)->toBeNull();
    Storage::disk('local')->assertExists($document->path);
});

it('forbids non-admins from uploading a SHEQ compliance document', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)
        ->post('/sheq-compliance', [
            'title' => 'HSE Policy',
            'file' => UploadedFile::fake()->create('hse-policy.pdf', 100),
        ])
        ->assertForbidden();

    expect(Media::where('collection', 'sheq_compliance')->count())->toBe(0);
});

it('allows any authenticated user (including clients) to view the SHEQ compliance index', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    Media::factory()->create();

    $this->actingAs($client)
        ->get('/sheq-compliance')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('documents.data', 1));
});

it('allows any authenticated user to download a SHEQ compliance document', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $file = UploadedFile::fake()->create('hse-policy.pdf', 100);
    $path = $file->storeAs('sheq-compliance', 'test.pdf', 'local');

    $document = Media::factory()->create([
        'disk' => 'local',
        'path' => $path,
        'original_name' => 'hse-policy.pdf',
    ]);

    $this->actingAs($client)
        ->get("/sheq-compliance/{$document->id}/download")
        ->assertSuccessful();
});

it('allows any authenticated user to view a SHEQ compliance document inline', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $file = UploadedFile::fake()->create('hse-policy.pdf', 100);
    $path = $file->storeAs('sheq-compliance', 'test.pdf', 'local');

    $document = Media::factory()->create([
        'disk' => 'local',
        'path' => $path,
        'original_name' => 'hse-policy.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->actingAs($client)
        ->get("/sheq-compliance/{$document->id}/view")
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename=hse-policy.pdf');
});

it('forbids non-admins from updating or deleting a SHEQ compliance document', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $document = Media::factory()->create();

    $this->actingAs($client)
        ->put("/sheq-compliance/{$document->id}", ['title' => 'New title'])
        ->assertForbidden();

    $this->actingAs($client)
        ->delete("/sheq-compliance/{$document->id}")
        ->assertForbidden();
});

it('allows admins to update and delete a SHEQ compliance document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $document = Media::factory()->create(['disk' => 'local']);

    $this->actingAs($admin)
        ->put("/sheq-compliance/{$document->id}", ['title' => 'Updated title'])
        ->assertRedirect();

    expect($document->fresh()->title)->toBe('Updated title');

    $this->actingAs($admin)
        ->delete("/sheq-compliance/{$document->id}")
        ->assertRedirect();

    expect(Media::find($document->id))->toBeNull();
});

it('does not allow a SHEQ compliance route to operate on media from a different collection', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $orderMedia = Media::factory()->create([
        'collection' => 'supporting_documents',
        'mediable_type' => 'App\\Models\\Order',
        'mediable_id' => 1,
    ]);

    $this->actingAs($admin)
        ->get("/sheq-compliance/{$orderMedia->id}/view")
        ->assertNotFound();

    $this->actingAs($admin)
        ->delete("/sheq-compliance/{$orderMedia->id}")
        ->assertNotFound();
});

it('does not allow a SHEQ compliance route to operate on order-attached media mislabeled with the sheq_compliance collection', function () {
    // Regression test: collection alone is not a trustworthy boundary - an order-attached
    // record must never be treated as a standalone SHEQ document even if its collection
    // happens to say so.
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $mislabeledOrderMedia = Media::factory()->create([
        'collection' => 'sheq_compliance',
        'mediable_type' => 'App\\Models\\Order',
        'mediable_id' => 1,
    ]);

    $this->actingAs($admin)
        ->get("/sheq-compliance/{$mislabeledOrderMedia->id}/view")
        ->assertNotFound();

    $this->actingAs($admin)
        ->delete("/sheq-compliance/{$mislabeledOrderMedia->id}")
        ->assertNotFound();

    expect(Media::find($mislabeledOrderMedia->id))->not->toBeNull();
});

it('excludes order-attached media mislabeled with the sheq_compliance collection from the index and unseen badge', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    Media::factory()->create([
        'collection' => 'sheq_compliance',
        'mediable_type' => 'App\\Models\\Order',
        'mediable_id' => 1,
    ]);

    $this->actingAs($client)
        ->get('/sheq-compliance')
        ->assertInertia(fn ($page) => $page->has('documents.data', 0));

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenSheqCompliance', false));
});

it('marks SHEQ compliance documents as seen when the user visits the index, clearing the unseen flag', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    Media::factory()->create();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenSheqCompliance', true));

    $this->actingAs($client)->get('/sheq-compliance');

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenSheqCompliance', false));
});

it('flags a newly uploaded SHEQ compliance document as unseen again for users who already visited', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $existing = Media::factory()->create();
    $existing->markSeenBy($client);

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenSheqCompliance', false));

    Media::factory()->create();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenSheqCompliance', true));
});

it('does not duplicate a content_view record when marking the same SHEQ compliance document seen twice', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $document = Media::factory()->create();

    $document->markSeenBy($client);
    $document->markSeenBy($client);

    expect(ContentView::where('viewable_type', Media::class)->where('viewable_id', $document->id)->where('user_id', $client->id)->count())->toBe(1);
});

it('does not flag SHEQ compliance as unseen when a document exists in a different media collection', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    Media::factory()->create([
        'collection' => 'supporting_documents',
        'mediable_type' => 'App\\Models\\Order',
        'mediable_id' => 1,
    ]);

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenSheqCompliance', false));
});
