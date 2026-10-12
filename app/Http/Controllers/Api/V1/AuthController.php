<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AuthenticateCredentials;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;

#[Group('Authentication', weight: 1)]
class AuthController extends Controller
{
    /**
     * Log in
     *
     * Exchanges an email/password pair for a Sanctum bearer token. Send the
     * returned token as `Authorization: Bearer <token>` on every other
     * request — this endpoint never starts a session. No token is issued for an
     * account that still has a system-generated temporary password; it must
     * be changed via the web app first. Rate limited to 6 requests/minute
     * per IP.
     */
    #[Response(401, description: 'Email/password did not match any account.')]
    #[Response(423, description: 'Account still has a temporary password (must_change_password) and cannot use the API yet.')]
    #[Response(429, description: 'Too many login attempts — limited to 6/minute per IP.')]
    public function login(LoginRequest $request, AuthenticateCredentials $authenticate)
    {
        // Issues a Sanctum token rather than starting a session. Same
        // generic error message as the web LoginController, so failed API
        // logins don't reveal whether an email is registered.
        $credentials = $request->validated();

        $user = $authenticate->execute($credentials['email'], $credentials['password']);

        if (! $user) {
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        if ($user->must_change_password) {
            return response()->json([
                'message' => 'This account must change its password before it can use the API. Log in to the web app to set a new password.',
                'error' => 'password_change_required',
            ], 423);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return (new UserResource($user->load(['role', 'functionalRoles'])))
            ->additional(['meta' => ['token' => $token]])
            ->response();
    }

    /**
     * Log out
     *
     * Revokes the bearer token used to authenticate this request only —
     * other active tokens/devices for the same user are left untouched.
     */
    public function logout(Request $request)
    {
        // Security review finding #4: auth:sanctum checks the 'web' session
        // guard before the bearer token (Laravel\Sanctum\Guard::__invoke()),
        // returning a TransientToken (no delete() method at all, unlike the
        // real PersonalAccessToken model) when it does. That path is live:
        // statefulApi() in bootstrap/app.php gives first-party browser
        // requests the session, so a session caller reaches this with a
        // TransientToken. This endpoint only revokes tokens, so for them it
        // is a deliberate no-op — browser sessions log out via the web
        // POST /logout. Catching the \Error (rather than an
        // instanceof/method_exists() check, which PHPStan flags as
        // always-true against Sanctum's PHPDoc-only return type) keeps that
        // a clean 204 instead of a hard crash.
        try {
            $request->user()->currentAccessToken()->delete();
        } catch (\Error) {
            // TransientToken has no delete() method at all — see above.
        }

        return response()->noContent();
    }

    /**
     * Get the current user
     *
     * Returns the authenticated user — equivalent to GET /users/{user} for
     * your own account, but doesn't require the users.view permission.
     */
    public function me(Request $request)
    {
        return new UserResource($request->user()->load(['role', 'functionalRoles']));
    }
}
