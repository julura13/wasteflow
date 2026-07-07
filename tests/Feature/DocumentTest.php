<?php

use App\Models\ContentView;
use App\Models\Document;
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

it('allows admins to upload a document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post('/documents', [
            'title' => 'Health & Safety Policy',
            'description' => 'Annual policy document.',
            'file' => UploadedFile::fake()->create('policy.pdf', 100),
        ])
        ->assertRedirect();

    $document = Document::first();
    expect($document)->not->toBeNull();
    expect($document->title)->toBe('Health & Safety Policy');
    expect($document->uploaded_by)->toBe($admin->id);
    Storage::disk('local')->assertExists($document->path);
});

it('forbids non-admins from uploading a document', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)
        ->post('/documents', [
            'title' => 'Health & Safety Policy',
            'file' => UploadedFile::fake()->create('policy.pdf', 100),
        ])
        ->assertForbidden();

    expect(Document::count())->toBe(0);
});

it('allows any authenticated user to view the documents index', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    Document::factory()->create();

    $this->actingAs($client)
        ->get('/documents')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('documents.data', 1));
});

it('allows any authenticated user to download a document', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $file = UploadedFile::fake()->create('policy.pdf', 100);
    $path = $file->storeAs('documents', 'test.pdf', 'local');

    $document = Document::factory()->create([
        'disk' => 'local',
        'path' => $path,
        'original_name' => 'policy.pdf',
    ]);

    $this->actingAs($client)
        ->get("/documents/{$document->id}/download")
        ->assertSuccessful();
});

it('forbids non-admins from updating or deleting a document', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $document = Document::factory()->create();

    $this->actingAs($client)
        ->put("/documents/{$document->id}", ['title' => 'New title'])
        ->assertForbidden();

    $this->actingAs($client)
        ->delete("/documents/{$document->id}")
        ->assertForbidden();
});

it('allows admins to update and delete a document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $document = Document::factory()->create(['disk' => 'local']);

    $this->actingAs($admin)
        ->put("/documents/{$document->id}", ['title' => 'Updated title'])
        ->assertRedirect();

    expect($document->fresh()->title)->toBe('Updated title');

    $this->actingAs($admin)
        ->delete("/documents/{$document->id}")
        ->assertRedirect();

    expect(Document::find($document->id))->toBeNull();
});

it('marks documents as seen when the user visits the index, clearing the unseen flag', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    Document::factory()->create();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenDocuments', true));

    $this->actingAs($client)->get('/documents');

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenDocuments', false));
});

it('flags a newly uploaded document as unseen again for users who already visited', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $existing = Document::factory()->create();
    $existing->markSeenBy($client);

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenDocuments', false));

    Document::factory()->create();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenDocuments', true));
});

it('does not duplicate a content_view record when marking the same document seen twice', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $document = Document::factory()->create();

    $document->markSeenBy($client);
    $document->markSeenBy($client);

    expect(ContentView::where('viewable_type', Document::class)->where('viewable_id', $document->id)->where('user_id', $client->id)->count())->toBe(1);
});
