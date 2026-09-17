<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:leave_types,name'],
            'default_days_per_year' => ['required', 'integer', 'min:0'],
            'paid' => ['boolean'],
            'is_unlimited' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'paid' => $this->boolean('paid'),
            'is_unlimited' => $this->boolean('is_unlimited'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
