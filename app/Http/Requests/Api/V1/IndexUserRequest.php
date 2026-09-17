<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    /**
     * Whitelisted `sort` values — never pass a client-supplied column
     * straight into orderBy(), which would let a request choose an
     * arbitrary (or non-existent) column.
     */
    public const SORTABLE_COLUMNS = ['name', 'email', 'created_at'];

    /**
     * There's no literal `status` column — `pending` means the account
     * still has a system-generated temporary password (must_change_password
     * = true); `active` means it's been through a real login/password
     * change. This mirrors the only two account states the app actually
     * models today.
     */
    public const STATUSES = ['active', 'pending'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(self::STATUSES)],
            'sort' => ['sometimes', Rule::in(self::SORTABLE_COLUMNS)],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
