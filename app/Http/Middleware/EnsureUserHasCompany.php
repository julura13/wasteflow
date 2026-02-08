<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->isCompanyUser() && count($user->company_ids) === 0) {
            abort(403, 'No company assigned. Please contact administrator.');
        }

        return $next($request);
    }
}
