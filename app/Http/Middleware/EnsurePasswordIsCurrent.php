<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsCurrent
{
    /**
     * Companion to the must_change_password check in AuthController::login()
     * (which stops a temporary-password account from ever obtaining a
     * token): this closes the gap for a token issued *before* an account
     * was later forced to change its password again, since ForcePasswordChange
     * only guards the `web` middleware group and has no equivalent here.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            abort(423, 'This account must change its password before it can use the API. Log in to the web app to set a new password.');
        }

        return $next($request);
    }
}
