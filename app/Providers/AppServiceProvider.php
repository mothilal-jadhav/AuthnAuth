<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        // Named limiters, not the bare "throttle:6,1" middleware: that form
        // keys solely on IP+domain (no route in the signature), so every
        // route using it would share one combined bucket per IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
    }
}
