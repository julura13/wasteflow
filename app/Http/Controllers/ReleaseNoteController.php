<?php

namespace App\Http\Controllers;

use App\Models\ReleaseNote;
use App\Models\ReleaseNoteRead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReleaseNoteController extends Controller
{
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
