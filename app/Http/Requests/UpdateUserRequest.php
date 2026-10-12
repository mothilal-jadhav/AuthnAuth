<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use App\Http\Requests\Concerns\HasNameEmailRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use HasNameEmailRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Shared by the web form and the API. `functional_role_ids` is optional:
     * a JSON client says "clear all" with `[]` and "leave untouched" by
     * omitting the key (the API controller reads that via has()); the web
     * form can't express the difference over HTML checkboxes, so it sends a
     * separate `functional_role_ids_submitted` marker instead.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($this->route('user')?->id),
            'role_id' => ['required', Rule::exists('roles', 'id')->where('type', RoleType::Hierarchy->value)],
            'functional_role_ids' => ['array'],
            'functional_role_ids.*' => [Rule::exists('roles', 'id')->where('type', RoleType::Functional->value)],
        ];
    }
}
