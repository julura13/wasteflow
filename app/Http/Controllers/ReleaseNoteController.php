<?php

namespace App\Http\Controllers;

use App\Models\ReleaseNote;
use App\Models\ReleaseNoteRead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseNoteController extends Controller
{
    /**
     * Full release notes history, newest first. Available to any authenticated user
     * (the version number that links here is visible to everyone in the header).
     */
    public function index(): Response
    {
        $notes = ReleaseNote::query()
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get(['id', 'version', 'type', 'title', 'description', 'released_at'])
            ->groupBy('version')
            ->map(fn ($notes, $version) => [
                'version' => $version,
                'released_at' => $notes->first()->released_at,
                'notes' => $notes->map(fn (ReleaseNote $note) => [
                    'id' => $note->id,
                    'type' => $note->type,
                    'title' => $note->title,
                    'description' => $note->description,
                ])->values(),
            ])
            ->values();

        return Inertia::render('ReleaseNotes/Index', [
            'versions' => $notes,
            'currentVersion' => config('app.version'),
        ]);
    }

    public function markAsRead(Request $request, ReleaseNote $releaseNote): RedirectResponse
    {
        ReleaseNoteRead::firstOrCreate(
            ['release_note_id' => $releaseNote->id, 'user_id' => $request->user()->id],
            ['read_at' => now()],
        );

        return back();
    }

    public function markNotificationAsRead(Request $request, string $notificationId): RedirectResponse
    {
        $request->user()->notifications()->find($notificationId)?->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        ReleaseNote::query()
            ->unreadByUser($user->id)
            ->each(function (ReleaseNote $note) use ($user) {
                ReleaseNoteRead::firstOrCreate(
                    ['release_note_id' => $note->id, 'user_id' => $user->id],
                    ['read_at' => now()],
                );
            });

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
