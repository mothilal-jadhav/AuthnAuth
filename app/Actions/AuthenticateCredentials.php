<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticateCredentials
{
    /**
     * A precomputed bcrypt hash of a fixed placeholder string (never a real
     * password). Checked against when no user matches the submitted email,
     * so Hash::check() always pays its usual cost either way — otherwise a
     * nonexistent email would short-circuit before ever reaching it, making
     * "no such email" measurably faster than "wrong password" despite both
     * getting the same generic message.
     */
    private const DUMMY_HASH = '$2y$12$1kF/SCd5h.67y0Re7o4nfexAosZDYWzsCtrimh7.SYQWf2qTu96sC';

    /**
     * Return the user these credentials belong to, or null. Only checks —
     * never logs in or issues a token, so the web (session) and API (token)
     * logins share it. Not Auth::attempt(): that can't be given a fallback
     * hash, which is what closes the timing gap above.
     */
    public function execute(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        $passwordMatches = Hash::check($password, $user->password ?? self::DUMMY_HASH);

        return $user && $passwordMatches ? $user : null;
    }
}
