<?php

namespace App\Http\Controllers;

use App\Actions\ChangePassword;
use App\Actions\UpdateProfile;
use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\VerifyCurrentPasswordRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Profile', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => ['name' => $user->role->name, 'type' => $user->role->type->value],
            ],
        ]);
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

    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile)
    {
        $updateProfile->execute($request->user(), $request->validated());

        return redirect()->route('profile')->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(UpdateProfilePasswordRequest $request, ChangePassword $changePassword)
    {
        $changePassword->execute($request->user(), $request->validated('password'));

        return redirect()->route('profile')->with('success', 'Password updated successfully.');
    }
}
