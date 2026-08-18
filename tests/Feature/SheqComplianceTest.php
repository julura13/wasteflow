<?php

use App\Models\Company;
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

it('allows a user with the view-sheq-compliance permission to view the index', function () {
    $client = User::factory()->create();
    $client->assignRole('client');
    $client->givePermissionTo('view-sheq-compliance');

    Media::factory()->create();

    $this->actingAs($client)
        ->get('/sheq-compliance')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('documents.data', 1));
});

it('forbids a user without the view-sheq-compliance permission from viewing the index', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    Media::factory()->create();

    $this->actingAs($client)
        ->get('/sheq-compliance')
        ->assertForbidden();
});

it('allows a user with the view-sheq-compliance permission to download a SHEQ compliance document', function () {
    $client = User::factory()->create();
    $client->assignRole('client');
    $client->givePermissionTo('view-sheq-compliance');

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

it('allows a user with the view-sheq-compliance permission to view a SHEQ compliance document inline', function () {
    $client = User::factory()->create();
    $client->assignRole('client');
    $client->givePermissionTo('view-sheq-compliance');

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

it('forbids a user without the view-sheq-compliance permission from downloading or viewing a SHEQ compliance document', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $document = Media::factory()->create(['disk' => 'local']);

    $this->actingAs($client)->get("/sheq-compliance/{$document->id}/download")->assertForbidden();
    $this->actingAs($client)->get("/sheq-compliance/{$document->id}/view")->assertForbidden();
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

it('allows updating a SHEQ compliance document without a file when the file field is an empty string, as Inertia sends it', function () {
    // Regression test: Inertia's useForm + forceFormData serializes a null `file` field as an
    // empty string rather than omitting it, which used to fail `sometimes|file` validation.
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $document = Media::factory()->create(['disk' => 'local']);

    $this->actingAs($admin)
        ->post("/sheq-compliance/{$document->id}", [
            '_method' => 'put',
            'title' => 'Updated title',
            'description' => '',
            'file' => '',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($document->fresh()->title)->toBe('Updated title');
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
    $client->givePermissionTo('view-sheq-compliance');

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
    $client->givePermissionTo('view-sheq-compliance');

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
    $client->givePermissionTo('view-sheq-compliance');

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
    $client->givePermissionTo('view-sheq-compliance');

    Media::factory()->create([
        'collection' => 'supporting_documents',
        'mediable_type' => 'App\\Models\\Order',
        'mediable_id' => 1,
    ]);

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenSheqCompliance', false));
});

it('never flags SHEQ compliance as unseen for a user without the view-sheq-compliance permission, even when unseen documents exist', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    Media::factory()->create();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('hasUnseenSheqCompliance', false));
});

it('appends a newly uploaded SHEQ compliance document to the end of the sort order', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Media::factory()->create(['sort_order' => 1]);
    Media::factory()->create(['sort_order' => 2]);

    $this->actingAs($admin)
        ->post('/sheq-compliance', [
            'title' => 'New Policy',
            'file' => UploadedFile::fake()->create('new-policy.pdf', 100),
        ])
        ->assertRedirect();

    $document = Media::where('title', 'New Policy')->first();
    expect($document->sort_order)->toBe(3);
});

it('orders the SHEQ compliance index by sort_order', function () {
    $client = User::factory()->create();
    $client->assignRole('client');
    $client->givePermissionTo('view-sheq-compliance');

    $third = Media::factory()->create(['title' => 'Third', 'sort_order' => 3]);
    $first = Media::factory()->create(['title' => 'First', 'sort_order' => 1]);
    $second = Media::factory()->create(['title' => 'Second', 'sort_order' => 2]);

    $this->actingAs($client)
        ->get('/sheq-compliance')
        ->assertInertia(fn ($page) => $page
            ->where('documents.data.0.title', 'First')
            ->where('documents.data.1.title', 'Second')
            ->where('documents.data.2.title', 'Third')
        );
});

it('allows an admin to move a SHEQ compliance document up, swapping with the previous document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $first = Media::factory()->create(['sort_order' => 1]);
    $second = Media::factory()->create(['sort_order' => 2]);

    $this->actingAs($admin)
        ->post("/sheq-compliance/{$second->id}/move-up")
        ->assertRedirect();

    expect($first->fresh()->sort_order)->toBe(2);
    expect($second->fresh()->sort_order)->toBe(1);
});

it('allows an admin to move a SHEQ compliance document down, swapping with the next document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $first = Media::factory()->create(['sort_order' => 1]);
    $second = Media::factory()->create(['sort_order' => 2]);

    $this->actingAs($admin)
        ->post("/sheq-compliance/{$first->id}/move-down")
        ->assertRedirect();

    expect($first->fresh()->sort_order)->toBe(2);
    expect($second->fresh()->sort_order)->toBe(1);
});

it('does not change sort_order when moving the first document up or the last document down', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $first = Media::factory()->create(['sort_order' => 1]);
    $second = Media::factory()->create(['sort_order' => 2]);

    $this->actingAs($admin)->post("/sheq-compliance/{$first->id}/move-up")->assertRedirect();
    expect($first->fresh()->sort_order)->toBe(1);
    expect($second->fresh()->sort_order)->toBe(2);

    $this->actingAs($admin)->post("/sheq-compliance/{$second->id}/move-down")->assertRedirect();
    expect($first->fresh()->sort_order)->toBe(1);
    expect($second->fresh()->sort_order)->toBe(2);
});

it('forbids non-admins from reordering SHEQ compliance documents', function () {
    $client = User::factory()->create();
    $client->assignRole('client');

    $first = Media::factory()->create(['sort_order' => 1]);
    $second = Media::factory()->create(['sort_order' => 2]);

    $this->actingAs($client)->post("/sheq-compliance/{$second->id}/move-up")->assertForbidden();
    $this->actingAs($client)->post("/sheq-compliance/{$first->id}/move-down")->assertForbidden();

    expect($first->fresh()->sort_order)->toBe(1);
    expect($second->fresh()->sort_order)->toBe(2);
});

it('does not allow reordering a SHEQ compliance route to operate on media from a different collection', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $orderMedia = Media::factory()->create([
        'collection' => 'supporting_documents',
        'mediable_type' => 'App\\Models\\Order',
        'mediable_id' => 1,
        'sort_order' => 1,
    ]);

    $this->actingAs($admin)
        ->post("/sheq-compliance/{$orderMedia->id}/move-up")
        ->assertNotFound();
});

it('ignores a stale company_ids field in the upload request, since visibility is folder-level not per-document', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $company = Company::query()->create(['name' => 'Acme', 'is_active' => true]);

    $this->actingAs($admin)
        ->post('/sheq-compliance', [
            'title' => 'Policy',
            'file' => UploadedFile::fake()->create('policy.pdf', 100),
            'company_ids' => [$company->id],
        ])
        ->assertRedirect();

    $document = Media::where('title', 'Policy')->first();
    expect($document)->not->toBeNull();
    expect($document->companies()->count())->toBe(0);
});

it('shows every SHEQ compliance document to any user with the view-sheq-compliance permission, regardless of company', function () {
    $ownCompany = Company::query()->create(['name' => 'Own Co', 'is_active' => true]);
    $otherCompany = Company::query()->create(['name' => 'Other Co', 'is_active' => true]);

    $client = User::factory()->create();
    $client->assignRole('client');
    $client->givePermissionTo('view-sheq-compliance');
    $client->companies()->attach($ownCompany->id);

    Media::factory()->create(['title' => 'From Other Co Context']);
    Media::factory()->create(['title' => 'Another Document']);

    $this->actingAs($client)
        ->get('/sheq-compliance')
        ->assertInertia(fn ($page) => $page->has('documents.data', 2));
});
