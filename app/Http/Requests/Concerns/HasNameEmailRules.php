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
