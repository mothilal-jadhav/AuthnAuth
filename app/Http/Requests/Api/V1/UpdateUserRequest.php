<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Concerns\HasNameEmailRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use HasNameEmailRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * `functional_role_ids` is intentionally optional with no "presence
     * marker" hack (unlike the web form, which needs one to distinguish
     * "every checkbox unchecked" from "field not submitted" over HTML
     * forms) — a JSON client can express "clear all" as `[]` and "leave
     * untouched" by omitting the key entirely, which the controller reads
     * via $request->has().
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($this->route('user')?->id),
            'role_id' => ['required', Rule::exists('roles', 'id')->where('type', 'hierarchy')],
            'department_id' => ['nullable', 'exists:departments,id'],
            'functional_role_ids' => ['array'],
            'functional_role_ids.*' => [Rule::exists('roles', 'id')->where('type', 'functional')],
        ];
    }
}
