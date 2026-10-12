<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
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
        // Generated before the view renders so @vite / @viteReactRefresh tag
        // their scripts with it. Inertia's progress bar and Vite's dev-mode
        // CSS injection pick it up from the root view's csp-nonce meta tag.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        // Vite dev server (`npm run dev`) only: its origin serves the
        // scripts/styles and its websocket drives HMR. public/hot never
        // exists in production or tests, so the policy stays strict there.
        $devServer = Vite::isRunningHot() ? rtrim((string) file_get_contents(Vite::hotFile())) : '';
        $devSocket = $devServer !== '' ? preg_replace('#^http#', 'ws', $devServer) : '';

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Content-Security-Policy', implode(' ', array_filter([
            "default-src 'self';",
            trim("script-src 'self' 'nonce-{$nonce}' {$devServer}").';',
            trim("style-src 'self' 'nonce-{$nonce}' {$devServer}").';',
            "img-src 'self' data:;",
            $devServer !== '' ? "connect-src 'self' {$devServer} {$devSocket};" : null,
            "frame-ancestors 'none';",
        ])));

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
