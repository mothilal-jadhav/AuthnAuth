<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            // Lazy: only resolved on pages that render through Inertia.
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'name' => $request->user()->name,
                    'role' => $request->user()->role->name,
                ] : null,
            ],
            // Flashed by the user update/delete redirects back to the list.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            // For plain HTML form posts that leave the Inertia app (e.g.
            // POST /logout). Inertia's own requests use the XSRF-TOKEN cookie.
            'csrf_token' => fn () => csrf_token(),
        ];
    }
}
