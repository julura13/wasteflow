<?php

use App\Models\ReleaseNote;
use App\Models\ReleaseNoteRead;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('shares unread release notes for admin users in bellNotifications', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $note = ReleaseNote::create([
        'version' => '1.1.0',
        'type' => 'feature',
        'title' => 'New feature',
        'description' => 'A cool new feature.',
        'released_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('bellNotifications', 1)
            ->where('bellNotifications.0.id', (string) $note->id)
            ->where('bellNotifications.0.badge_type', 'feature')
            ->where('bellNotifications.0.kind', 'release_note')
        );
});

it('includes system notifications in bellNotifications for admin users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $admin->notifications()->create([
        'id' => \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\DatabaseBackupStatusNotification',
        'data' => ['kind' => 'backup', 'badge_type' => 'success', 'badge_label' => 'succeeded', 'title' => 'Backup done', 'description' => null],
        'read_at' => null,
    ]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('bellNotifications', 1)
            ->where('bellNotifications.0.kind', 'backup')
        );
});

it('does not share notifications for non-admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('company_user');

    ReleaseNote::create([
        'version' => '1.0.0',
        'type' => 'bugfix',
        'title' => 'A bug fix',
        'description' => null,
        'released_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('bellNotifications', []));
});

it('excludes already-read release notes from bellNotifications', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $note = ReleaseNote::create([
        'version' => '1.0.0',
        'type' => 'improvement',
        'title' => 'Performance improvements',
        'description' => null,
        'released_at' => now(),
    ]);

    ReleaseNoteRead::create([
        'release_note_id' => $note->id,
        'user_id' => $admin->id,
        'read_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('bellNotifications', []));
});

it('marks a single release note as read', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $note = ReleaseNote::create([
        'version' => '1.0.0',
        'type' => 'feature',
        'title' => 'Something new',
        'description' => null,
        'released_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post("/release-notes/{$note->id}/read")
        ->assertRedirect();

    expect(ReleaseNoteRead::where('release_note_id', $note->id)->where('user_id', $admin->id)->exists())->toBeTrue();
});

it('marks a system notification as read', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $uuid = (string) \Illuminate\Support\Str::uuid();
    $admin->notifications()->create([
        'id' => $uuid,
        'type' => 'App\\Notifications\\DatabaseBackupStatusNotification',
        'data' => ['kind' => 'backup', 'badge_type' => 'success', 'badge_label' => 'succeeded', 'title' => 'Backup done', 'description' => null],
        'read_at' => null,
    ]);

    $this->actingAs($admin)
        ->post("/notifications/{$uuid}/read")
        ->assertRedirect();

    expect(DatabaseNotification::find($uuid)->read_at)->not->toBeNull();
});

it('marks all notifications as read including system notifications', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    ReleaseNote::insert([
        ['version' => '1.0.0', 'type' => 'feature', 'title' => 'Feature A', 'description' => null, 'released_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ['version' => '1.0.0', 'type' => 'bugfix', 'title' => 'Bug fix B', 'description' => null, 'released_at' => now(), 'created_at' => now(), 'updated_at' => now()],
    ]);

    $uuid = (string) \Illuminate\Support\Str::uuid();
    $admin->notifications()->create([
        'id' => $uuid,
        'type' => 'App\\Notifications\\DatabaseBackupStatusNotification',
        'data' => ['kind' => 'backup', 'badge_type' => 'success', 'badge_label' => 'succeeded', 'title' => 'Backup done', 'description' => null],
        'read_at' => null,
    ]);

    $this->actingAs($admin)
        ->post('/release-notes/read-all')
        ->assertRedirect();

    expect(ReleaseNoteRead::where('user_id', $admin->id)->count())->toBe(2);
    expect(DatabaseNotification::find($uuid)->read_at)->not->toBeNull();
});

it('does not duplicate a read record when marking the same note twice', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $note = ReleaseNote::create([
        'version' => '1.0.0',
        'type' => 'feature',
        'title' => 'Something new',
        'description' => null,
        'released_at' => now(),
    ]);

    $this->actingAs($admin)->post("/release-notes/{$note->id}/read");
    $this->actingAs($admin)->post("/release-notes/{$note->id}/read");

    expect(ReleaseNoteRead::where('release_note_id', $note->id)->where('user_id', $admin->id)->count())->toBe(1);
});

it('shows the full release notes history grouped by version to any authenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole('company_user');

    ReleaseNote::insert([
        ['version' => '1.8.0', 'type' => 'feature', 'title' => 'Newer feature', 'description' => 'Newer.', 'released_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ['version' => '1.8.0', 'type' => 'bugfix', 'title' => 'Newer fix', 'description' => 'Also newer.', 'released_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ['version' => '0.4.1', 'type' => 'improvement', 'title' => 'Older improvement', 'description' => 'Older.', 'released_at' => now()->subDay(), 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
    ]);

    $this->actingAs($user)
        ->get(route('release-notes.index'))
        ->assertInertia(fn ($page) => $page
            ->component('ReleaseNotes/Index')
            ->has('versions', 2)
            ->where('versions.0.version', '1.8.0')
            ->has('versions.0.notes', 2)
            ->where('versions.1.version', '0.4.1')
        );
});

it('returns forbidden for non-admin users trying to mark notes as read', function () {
    $user = User::factory()->create();
    $user->assignRole('company_user');

    $note = ReleaseNote::create([
        'version' => '1.0.0',
        'type' => 'feature',
        'title' => 'Something new',
        'description' => null,
        'released_at' => now(),
    ]);

    $this->actingAs($user)
        ->post("/release-notes/{$note->id}/read")
        ->assertForbidden();
});
