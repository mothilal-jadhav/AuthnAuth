<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        if (! $request->user()) {
            abort(401, 'Unauthenticated.');
        }

        if (! $request->user()->hasPermission($permission)) {
            abort(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
