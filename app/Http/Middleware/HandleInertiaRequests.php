<?php

namespace App\Http\Middleware;

use App\Models\Document;
use App\Models\ReleaseNote;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Lab404\Impersonate\Services\ImpersonateManager;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'rebate_pdf_export_uuid' => fn () => $request->session()->get('rebate_pdf_export_uuid'),
                'waste_management_pdf_export_uuid' => fn () => $request->session()->get('waste_management_pdf_export_uuid'),
                'waste_stream_collection_pdf_export_uuid' => fn () => $request->session()->get('waste_stream_collection_pdf_export_uuid'),
                'order_export_uuid' => fn () => $request->session()->get('order_export_uuid'),
                'order_export_format' => fn () => $request->session()->get('order_export_format'),
            ],
            'app' => [
                'version' => config('app.version'),
            ],
            'auth' => [
                'user' => fn () => $this->authUserPayload($request->user()),
            ],
            'impersonating' => fn () => app(ImpersonateManager::class)->isImpersonating(),
            'mapbox' => [
                'access_token' => config('services.mapbox.access_token'),
            ],
            'bellNotifications' => function () use ($request) {
                $user = $request->user();

                return $user && $user->isAdmin() ? $this->bellNotifications($user) : [];
            },
            'hasUnseenDocuments' => function () use ($request) {
                $user = $request->user();

                return $user ? Document::query()->unseenByUser($user->id)->exists() : false;
            },
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authUserPayload(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'is_admin' => $user->isAdmin(),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bellNotifications(User $user): array
    {
        $releaseNotes = ReleaseNote::query()
            ->unreadByUser($user->id)
            ->orderByDesc('released_at')
            ->get()
            ->map(fn ($note) => [
                'id' => (string) $note->id,
                'kind' => 'release_note',
                'badge_type' => $note->type,
                'badge_label' => $note->type,
                'title' => $note->title,
                'description' => $note->description,
                'read_url' => "/release-notes/{$note->id}/read",
            ]);

        $systemNotifications = $user->unreadNotifications()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'kind' => $n->data['kind'] ?? 'system',
                'badge_type' => $n->data['badge_type'] ?? 'info',
                'badge_label' => $n->data['badge_label'] ?? 'system',
                'title' => $n->data['title'] ?? 'Notification',
                'description' => $n->data['description'] ?? null,
                'read_url' => "/notifications/{$n->id}/read",
            ]);

        return $releaseNotes->concat($systemNotifications)->values()->all();
    }
}
