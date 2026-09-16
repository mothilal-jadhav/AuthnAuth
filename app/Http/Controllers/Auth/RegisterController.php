<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        try {
            $userRole = Role::where('name', 'user')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            report($e);

            return back()->withErrors([
                'email' => 'Registration is temporarily unavailable. Please try again later.',
            ])->onlyInput('name', 'email');
        }

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $user->role_id = $userRole->id;
        $user->save();

        Auth::login($user);

        ActivityLog::record('user.registered', $user, "{$user->name} self-registered.");

        $request->session()->regenerate();

        return redirect('/dashboard');
    }
}
