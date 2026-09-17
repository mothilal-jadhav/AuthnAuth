<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveBalanceRequest extends FormRequest
{
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
            'allocated_days' => ['required', 'numeric', 'min:0'],
            'carried_over_days' => ['required', 'numeric', 'min:0'],
            'used_days' => ['required', 'numeric', 'min:0'],
        ];
    }
}
