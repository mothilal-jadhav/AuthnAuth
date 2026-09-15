<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasNameEmailRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    use HasNameEmailRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [User::class, (int) $this->input('role_id')]);
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
            'email' => $this->emailRules(),
            'role_id' => ['required', 'exists:roles,id'],
        ];
    }
}
