<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class LoginController extends Controller
{
    /**
     * Same purpose as the identical constant on Api\V1\AuthController: a
     * precomputed bcrypt hash of a fixed placeholder string, checked when no
     * user matches the submitted email so Hash::check() always runs,
     * instead of a nonexistent email short-circuiting before ever reaching
     * it and being measurably faster than a wrong password.
     */
    private const DUMMY_HASH = '$2y$12$1kF/SCd5h.67y0Re7o4nfexAosZDYWzsCtrimh7.SYQWf2qTu96sC';

    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Not Auth::attempt() — it can't be given a fallback hash to check
        // against when no user matches, which is what closes the timing gap
        // below. Same manual-check shape as the API's AuthController::login().
        $user = User::where('email', $credentials['email'])->first();

        $passwordMatches = Hash::check($credentials['password'], $user->password ?? self::DUMMY_HASH);

        if ($user && $passwordMatches) {
            Auth::login($user);
            $request->session()->regenerate();

            // A full page load, not an Inertia visit: the dashboard is still
            // a Blade page, which an Inertia XHR can't swap in. For a plain
            // (non-Inertia) POST this is an ordinary redirect.
            return Inertia::location('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}
