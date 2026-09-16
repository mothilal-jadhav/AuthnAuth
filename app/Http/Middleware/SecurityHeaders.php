<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Set a baseline of security-related response headers on every
     * request. The CSP has no 'unsafe-inline' for script/style: the app
     * has no inline <script>, <style>, or on*= handlers left (theme
     * bootstrap and confirm dialogs are external files under public/js),
     * so a strict policy doesn't break anything here.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Content-Security-Policy', implode(' ', [
            "default-src 'self';",
            "script-src 'self';",
            "style-src 'self';",
            "img-src 'self' data:;",
            "frame-ancestors 'none';",
        ]));

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
