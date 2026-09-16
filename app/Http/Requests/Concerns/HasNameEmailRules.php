<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait HasNameEmailRules
{
    protected function nameRules(): array
    {
        return ['required', 'string', 'regex:/^[a-zA-Z\s]+$/', 'max:255'];
    }

    protected function emailRules(?int $ignoreUserId = null): array
    {
        // Deliberately NOT scoped to whereNull('deleted_at'): the `email`
        // column has a plain DB-level unique index, and MySQL has no
        // partial/filtered unique index support, so the constraint still
        // blocks a soft-deleted user's email regardless of what this
        // validation rule allows. Scoping just the validation would let a
        // request pass here and then fail with a raw DB integrity-violation
        // 500 instead of a clean validation error. A trashed user's email
        // stays reserved until they're restored (or a future change adds
        // email-mangling-on-delete to free it up).
        $unique = Rule::unique('users', 'email');

        if ($ignoreUserId !== null) {
            $unique = $unique->ignore($ignoreUserId);
        }

        return [
            'required',
            'email',
            'max:255',
            'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
            $unique,
        ];
    }
}
