<?php

namespace App\Http\Requests;

use App\Models\AttendanceRegularization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRegularizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AttendanceRegularization::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'requested_clock_in' => ['required', 'date_format:H:i'],
            'requested_clock_out' => ['required', 'date_format:H:i', 'after:requested_clock_in'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
