<?php

namespace App\Http\Middleware;

use App\Models\ClientHubAdvert;
use App\Models\Document;
use App\Models\Media;
use App\Models\ReleaseNote;
use App\Models\User;
use App\Services\CompanyUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;
use Lab404\Impersonate\Services\ImpersonateManager;

class HandleInertiaRequests extends Middleware
{
    /**
     * Cache for activeClientHubAdvertsWithViewsFor() within a single request - clientHubPopupAdvert
     * and bellNotifications both need "active adverts + this user's view state" and would otherwise
     * each run their own near-identical query on every request for a client-role user.
     */
    private ?Collection $clientHubAdvertsForUser = null;

    public function __construct(private readonly CompanyUserService $companyUserService) {}

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

                if (! $user) {
                    return [];
                }

                if ($user->isAdmin()) {
                    return $this->bellNotifications($user);
                }

                return $user->hasRole('client') ? $this->clientHubBellNotifications($user) : [];
            },
            'hasUnseenDocuments' => function () use ($request) {
                $user = $request->user();

                return $user ? Document::query()->unseenByUser($user->id)->exists() : false;
            },
            'hasUnseenSheqCompliance' => function () use ($request) {
                $user = $request->user();

                if (! $user) {
                    return false;
                }

                $query = Media::query()->where('collection', 'sheq_compliance')->whereNull('mediable_type');

                return $this->companyUserService->scopeVisibleToUser($query, $user)->unseenByUser($user->id)->exists();
            },
            'clientHubPopupAdvert' => function () use ($request) {
                $user = $request->user();

                if (! $user || ! $user->hasRole('client')) {
                    return null;
                }

                $advert = $this->activeClientHubAdvertsWithViewsFor($user)
                    ->first(fn (ClientHubAdvert $a) => is_null($a->views->first()?->dismissed_at));

                return $advert ? [
                    'id' => $advert->id,
                    'title' => $advert->title,
                    'details' => $advert->details,
                    'contact_email' => $advert->contact_email,
                    'mime_type' => $advert->mime_type,
                    'view_url' => route('client-hub.view', $advert->id),
                ] : null;
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

    /**
     * @return list<array<string, mixed>>
     */
    private function clientHubBellNotifications(User $user): array
    {
        return $this->activeClientHubAdvertsWithViewsFor($user)
            ->filter(fn (ClientHubAdvert $a) => is_null($a->views->first()?->read_at))
            ->map(fn (ClientHubAdvert $advert) => [
                'id' => (string) $advert->id,
                'kind' => 'client_hub_advert',
                'badge_type' => 'announcement',
                'badge_label' => 'new',
                'title' => $advert->title,
                'description' => $advert->details,
                'image_url' => route('client-hub.view', $advert->id),
                'mime_type' => $advert->mime_type,
                'contact_email' => $advert->contact_email,
                'read_url' => "/client-hub/{$advert->id}/read",
            ])
            ->values()
            ->all();
    }

    /**
     * Active adverts with this user's ClientHubAdvertView eager-loaded (0 or 1 per advert, per
     * the unique(client_hub_advert_id, user_id) constraint) - fetched once per request and shared
     * between clientHubPopupAdvert and clientHubBellNotifications.
     *
     * @return Collection<int, ClientHubAdvert>
     */
    private function activeClientHubAdvertsWithViewsFor(User $user): Collection
    {
        if ($this->clientHubAdvertsForUser === null) {
            $this->clientHubAdvertsForUser = ClientHubAdvert::query()
                ->active()
                ->with(['views' => fn ($q) => $q->where('user_id', $user->id)])
                ->latest()
                ->get();
        }

        return $this->clientHubAdvertsForUser;
    }
}
