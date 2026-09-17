<?php

namespace App\Http\Requests;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveCalculator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LeaveRequest::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_half_day' => $this->boolean('is_half_day')]);
    }

    /**
     * Overlap and balance checks need DB state beyond simple field rules,
     * so they live here rather than in rules() — same reasoning as any
     * other cross-field, DB-dependent FormRequest validation in this app.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->boolean('is_half_day') && $this->input('start_date') !== $this->input('end_date')) {
                $validator->errors()->add('is_half_day', 'A half-day request must have the same start and end date.');

                return;
            }

            $totalDays = LeaveCalculator::workingDaysBetween(
                $this->input('start_date'),
                $this->input('end_date'),
                $this->boolean('is_half_day')
            );

            $overlaps = LeaveRequest::where('user_id', $this->user()->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where('start_date', '<=', $this->input('end_date'))
                ->where('end_date', '>=', $this->input('start_date'))
                ->exists();

            if ($overlaps) {
                $validator->errors()->add('start_date', 'You already have a pending or approved leave request overlapping these dates.');

                return;
            }

            $leaveType = LeaveType::find($this->input('leave_type_id'));

            if ($leaveType && ! $leaveType->is_unlimited) {
                $year = (int) date('Y', strtotime((string) $this->input('start_date')));

                $balance = LeaveBalance::where('user_id', $this->user()->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $year)
                    ->first();

                $remaining = $balance
                    ? $balance->allocated_days + $balance->carried_over_days - $balance->used_days
                    : $leaveType->default_days_per_year;

                if ($totalDays > $remaining) {
                    $validator->errors()->add('leave_type_id', "Insufficient {$leaveType->name} balance: {$remaining} day(s) remaining.");
                }
            }
        });
    }
}
