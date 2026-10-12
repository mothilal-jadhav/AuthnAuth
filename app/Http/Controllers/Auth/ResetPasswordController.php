<?php

namespace App\Http\Controllers\Auth;

use App\Actions\ChangePassword;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request, ChangePassword $changePassword)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $status = PasswordBroker::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, $password) use ($changePassword) {
                $changePassword->execute($user, $password);

                event(new PasswordReset($user));
            }
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            return redirect('/login')
                ->with('status', __($status));
        }

        return back()->withErrors([
            'email' => __($status),
        ]);
    }
}
