<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'impersonate' => \Lab404\Impersonate\Middleware\ProtectFromImpersonation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 403 && ($request->expectsJson() || $request->header('X-Inertia'))) {
                if ($request->header('X-Inertia')) {
                    return \Inertia\Inertia::render('Errors/403', [
                        'status' => 403,
                        'message' => $e->getMessage() ?: 'This action is unauthorized.',
                    ])->toResponse($request)->setStatusCode(403);
                }

                return response()->json([
                    'message' => $e->getMessage() ?: 'This action is unauthorized.',
                    'status' => 403,
                ], 403);
            }
        });
    })->create();
