<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

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
        $user = $request->user();

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'rebate_pdf_export_uuid' => fn () => $request->session()->get('rebate_pdf_export_uuid'),
                'waste_management_pdf_export_uuid' => fn () => $request->session()->get('waste_management_pdf_export_uuid'),
                'order_export_uuid' => fn () => $request->session()->get('order_export_uuid'),
                'order_export_format' => fn () => $request->session()->get('order_export_format'),
            ],
            'app' => [
                'version' => config('app.version'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'is_admin' => $user->isAdmin(),
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'mapbox' => [
                'access_token' => config('services.mapbox.access_token'),
            ],
        ];
    }
}
