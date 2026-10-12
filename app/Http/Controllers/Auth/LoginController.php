<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AuthenticateCredentials;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // `status` is flashed by a successful password reset.
        return Inertia::render('Auth/Login', ['status' => session('status')]);
    }

    public function login(LoginRequest $request, AuthenticateCredentials $authenticate)
    {
        $credentials = $request->validated();

        $user = $authenticate->execute($credentials['email'], $credentials['password']);

        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
