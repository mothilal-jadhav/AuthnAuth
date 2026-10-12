<?php

namespace App\Http\Controllers\Auth;

use App\Actions\ChangePassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use Inertia\Inertia;

class ChangePasswordController extends Controller
{
    public function showForm()
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function update(ChangePasswordRequest $request, ChangePassword $changePassword)
    {
        $changePassword->execute($request->user(), $request->validated('password'));

        return redirect('/dashboard')->with('success', 'Password updated successfully.');
    }
}
