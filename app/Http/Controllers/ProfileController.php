<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\ActivityLog;
use App\Notifications\EmailChangedNotification;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Support\Facades\Notification;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile');
    }

    public function update(UpdateProfileRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();
        $oldEmail = $user->email;

        $user->fill($validated);
        $user->save();

        $changes = $user->getChanges();
        unset($changes['updated_at']);

        if ($changes !== []) {
            ActivityLog::record(
                'user.updated',
                $user,
                "{$user->name} updated their own profile.",
                $changes
            );
        }

        if (array_key_exists('email', $changes)) {
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

        ActivityLog::record('user.password_changed', $user, "{$user->name} changed their own password.");

        $user->notify(new PasswordChangedNotification);

        return redirect()->route('profile')->with('success', 'Password updated successfully.');
    }
}
