<?php

use App\Models\ClientHubAdvert;
use App\Models\ClientHubAdvertView;
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

it('allows an admin to upload a client hub advert', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post('/client-hub', [
            'title' => 'New Bag Service',
            'details' => 'We now offer compostable bags.',
            'file' => UploadedFile::fake()->image('advert.png'),
        ])
        ->assertRedirect();

    $advert = ClientHubAdvert::first();
    expect($advert)->not->toBeNull();
    expect($advert->title)->toBe('New Bag Service');
    expect($advert->contact_email)->toBe('crm@wasteflow.example.com');
    expect($advert->is_active)->toBeTrue();
    Storage::disk('local')->assertExists($advert->path);
});

it('rejects a file type outside png/jpg/pdf', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post('/client-hub', [
            'title' => 'Bad File',
            'file' => UploadedFile::fake()->create('advert.txt', 100),
        ])
        ->assertSessionHasErrors('file');

    expect(ClientHubAdvert::count())->toBe(0);
});

it('forbids non-admins from uploading, updating, or deleting a client hub advert', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->post('/client-hub', [
            'title' => 'New Bag Service',
            'file' => UploadedFile::fake()->image('advert.png'),
        ])
        ->assertForbidden();

    $advert = ClientHubAdvert::factory()->create();

    $this->actingAs($manager)
        ->put("/client-hub/{$advert->id}", ['title' => 'Renamed'])
        ->assertForbidden();

    $this->actingAs($manager)
        ->delete("/client-hub/{$advert->id}")
        ->assertForbidden();

    expect(ClientHubAdvert::count())->toBe(1);
});

it('allows an admin to update and delete a client hub advert', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $advert = ClientHubAdvert::factory()->create(['disk' => 'local']);

    $this->actingAs($admin)
        ->put("/client-hub/{$advert->id}", [
            'title' => 'Updated title',
            'contact_email' => 'sales@wasteflow.example.com',
            'is_active' => false,
        ])
        ->assertRedirect();

    $advert->refresh();
    expect($advert->title)->toBe('Updated title');
    expect($advert->contact_email)->toBe('sales@wasteflow.example.com');
    expect($advert->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->delete("/client-hub/{$advert->id}")
        ->assertRedirect();

    expect(ClientHubAdvert::find($advert->id))->toBeNull();
});

it('shares an active undismissed advert as the popup prop for a client user, but not for other roles', function () {
    ClientHubAdvert::factory()->create(['title' => 'New Service']);

    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('clientHubPopupAdvert.title', 'New Service')
        );

    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->actingAs($manager)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('clientHubPopupAdvert', null));
});

it('excludes an inactive advert from the popup and bell notifications', function () {
    ClientHubAdvert::factory()->create(['is_active' => false]);

    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('clientHubPopupAdvert', null)
            ->where('bellNotifications', [])
        );
});

it('dismissing the popup stops it reappearing but leaves the notification badge unread', function () {
    $advert = ClientHubAdvert::factory()->create();

    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)
        ->post("/client-hub/{$advert->id}/dismiss")
        ->assertRedirect();

    $view = ClientHubAdvertView::where('client_hub_advert_id', $advert->id)->where('user_id', $client->id)->first();
    expect($view)->not->toBeNull();
    expect($view->dismissed_at)->not->toBeNull();
    expect($view->read_at)->toBeNull();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('clientHubPopupAdvert', null)
            ->has('bellNotifications', 1)
            ->where('bellNotifications.0.kind', 'client_hub_advert')
        );
});

it('reading the advert from the bell marks both flags and clears the notification badge', function () {
    $advert = ClientHubAdvert::factory()->create();

    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)
        ->post("/client-hub/{$advert->id}/read")
        ->assertRedirect();

    $view = ClientHubAdvertView::where('client_hub_advert_id', $advert->id)->where('user_id', $client->id)->first();
    expect($view->dismissed_at)->not->toBeNull();
    expect($view->read_at)->not->toBeNull();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('clientHubPopupAdvert', null)
            ->where('bellNotifications', [])
        );
});

it('forbids non-client roles from dismissing or reading an advert', function () {
    $advert = ClientHubAdvert::factory()->create();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post("/client-hub/{$advert->id}/dismiss")
        ->assertForbidden();

    $this->actingAs($admin)
        ->post("/client-hub/{$advert->id}/read")
        ->assertForbidden();
});

it('keeps each client\'s dismissed/read state independent of other clients', function () {
    $advert = ClientHubAdvert::factory()->create();

    $clientA = User::factory()->create();
    $clientA->assignRole('client');
    $clientB = User::factory()->create();
    $clientB->assignRole('client');

    $this->actingAs($clientA)->post("/client-hub/{$advert->id}/dismiss");

    $this->actingAs($clientA)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('clientHubPopupAdvert', null));

    $this->actingAs($clientB)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('clientHubPopupAdvert.id', $advert->id));
});

it('serves the advert file inline to any authenticated user', function () {
    $file = UploadedFile::fake()->image('advert.png');
    $path = $file->storeAs('client-hub', 'test.png', 'local');

    $advert = ClientHubAdvert::factory()->create([
        'disk' => 'local',
        'path' => $path,
        'original_name' => 'advert.png',
        'mime_type' => 'image/png',
    ]);

    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)
        ->get("/client-hub/{$advert->id}/view")
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/png');
});

it('shows the admin management list with all adverts', function () {
    ClientHubAdvert::factory()->count(2)->create();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/client-hub')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/ClientHub/Index')
            ->has('adverts', 2)
        );
});

it('deletes the file from storage when an advert is deleted', function () {
    $file = UploadedFile::fake()->image('advert.png');
    $path = $file->storeAs('client-hub', 'test.png', 'local');

    $advert = ClientHubAdvert::factory()->create(['disk' => 'local', 'path' => $path]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->delete("/client-hub/{$advert->id}");

    Storage::disk('local')->assertMissing($path);
});

it('marks all unread client hub adverts as read for the acting client via read-all', function () {
    $advertA = ClientHubAdvert::factory()->create();
    $advertB = ClientHubAdvert::factory()->create();

    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)
        ->post('/client-hub/read-all')
        ->assertRedirect();

    $views = ClientHubAdvertView::where('user_id', $client->id)->get()->keyBy('client_hub_advert_id');
    expect($views->has($advertA->id))->toBeTrue();
    expect($views->has($advertB->id))->toBeTrue();
    expect($views[$advertA->id]->dismissed_at)->not->toBeNull();
    expect($views[$advertA->id]->read_at)->not->toBeNull();
    expect($views[$advertB->id]->read_at)->not->toBeNull();

    $this->actingAs($client)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('clientHubPopupAdvert', null)
            ->where('bellNotifications', [])
        );
});

it('does not let read-all affect another client\'s unread adverts', function () {
    $advert = ClientHubAdvert::factory()->create();

    $clientA = User::factory()->create();
    $clientA->assignRole('client');
    $clientB = User::factory()->create();
    $clientB->assignRole('client');

    $this->actingAs($clientA)->post('/client-hub/read-all');

    expect(ClientHubAdvertView::where('user_id', $clientB->id)->count())->toBe(0);

    $this->actingAs($clientB)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->has('bellNotifications', 1));
});

it('forbids non-client roles from calling read-all', function () {
    ClientHubAdvert::factory()->create();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post('/client-hub/read-all')
        ->assertForbidden();
});

it('dismiss does not throw when called twice for the same user and advert (race-safe upsert)', function () {
    $advert = ClientHubAdvert::factory()->create();

    $client = User::factory()->create();
    $client->assignRole('client');

    $this->actingAs($client)->post("/client-hub/{$advert->id}/dismiss")->assertRedirect();
    $this->actingAs($client)->post("/client-hub/{$advert->id}/dismiss")->assertRedirect();

    expect(ClientHubAdvertView::where('user_id', $client->id)->where('client_hub_advert_id', $advert->id)->count())->toBe(1);
});
