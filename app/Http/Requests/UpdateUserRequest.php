<?php

namespace App\Http\Requests;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($this->route('user')->id),
            'role_id' => ['required', Rule::exists('roles', 'id')->where('type', 'hierarchy')],
            'department_id' => ['nullable', 'exists:departments,id'],
            'functional_role_ids' => ['array'],
            'functional_role_ids.*' => [Rule::exists('roles', 'id')->where('type', 'functional')],
        ];
    }
}
