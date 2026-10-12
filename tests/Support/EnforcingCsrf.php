<?php

namespace Tests\Support;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

/**
 * Laravel's CSRF middleware skips itself entirely under PHPUnit
 * (PreventRequestForgery::runningUnitTests()), so a "missing token → 419"
 * test against the stock class can never fail. Swapped in only for the
 * stateful API path via config('sanctum.middleware.validate_csrf_token'),
 * which Sanctum reads per request, so the rest of the suite is unaffected.
 */
class EnforcingCsrf extends ValidateCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}
