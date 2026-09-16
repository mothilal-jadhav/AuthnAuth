<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasNameEmailRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use HasNameEmailRules;

    /**
     * Named error bag so a validation failure here doesn't get shown under
     * the separate password-change form on the same /profile page.
     */
    protected $errorBag = 'updateProfile';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'email' => $this->emailRules($this->user()->id),
        ];
    }
}
