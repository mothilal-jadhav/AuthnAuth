<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\VerifyCurrentPasswordRequest;
use App\Notifications\EmailChangedNotification;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Support\Facades\Notification;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile');
    }

    /**
     * Check the current password against the real hash without changing
     * anything — powers the profile page's two-step password-change UI
     * (reveal the new-password fields only after this succeeds). The actual
     * change endpoint re-validates `current_password` itself regardless, so
     * this is a UX pre-check, not a trust boundary.
     */
    public function verifyPassword(VerifyCurrentPasswordRequest $request)
    {
        return response()->json(['message' => 'Password verified.']);
    }

    public function update(UpdateProfileRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();
        $oldEmail = $user->email;

        $user->fill($validated);
        $user->save();

        if ($user->wasChanged('email')) {
            Notification::route('mail', array_unique([$oldEmail, $user->email]))
                ->notify(new EmailChangedNotification($oldEmail, $user->email, $user->name));
        }

        return redirect()->route('profile')->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(UpdateProfilePasswordRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();
        $user->password = $validated['password'];
        $user->save();

        // Security review finding #1: a password change must revoke any
        // existing API tokens, otherwise a stolen token stays valid even
        // after the account owner thinks they've secured their account.
        $user->tokens()->delete();

        $user->notify(new PasswordChangedNotification);

        return redirect()->route('profile')->with('success', 'Password updated successfully.');
    }
}
