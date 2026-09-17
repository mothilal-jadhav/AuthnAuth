<?php

namespace App\Providers;

use App\Models\AttendanceRegularization;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Policies\AttendanceRegularizationPolicy;
use App\Policies\LeavePolicy;
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
        Gate::policy(LeaveRequest::class, LeavePolicy::class);
        Gate::policy(AttendanceRegularization::class, AttendanceRegularizationPolicy::class);

        // Named limiters, not the bare "throttle:6,1" middleware: that form
        // keys solely on IP+domain (no route in the signature), so every
        // route using it would share one combined bucket per IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('api-login', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        // Post-auth endpoint (profile's password-change pre-check), so keyed
        // per user rather than per IP like the pre-auth limiters above.
        RateLimiter::for('password-verify', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        // Default limiter for the api middleware group (routes/api.php).
        // Individual endpoints (e.g. API login) get their own, stricter
        // named limiter where warranted, same pattern as `login` above.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
