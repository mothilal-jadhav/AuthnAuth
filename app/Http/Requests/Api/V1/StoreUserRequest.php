<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Concerns\HasNameEmailRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use HasNameEmailRules;

    public function authorize(): bool
    {
        return $this->user()->can('create', [User::class, (int) $this->input('role_id')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules(),
            'role_id' => ['required', Rule::exists('roles', 'id')->where('type', 'hierarchy')],
            'department_id' => ['nullable', 'exists:departments,id'],
        ];
    }
}
