<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendancePolicyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'early_leave_grace_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'full_day_minutes' => ['required', 'integer', 'min:60', 'max:1440'],
            'half_day_minutes' => ['required', 'integer', 'min:30', 'lte:full_day_minutes'],
            'overtime_after_minutes' => ['required', 'integer', 'min:60', 'max:1440'],
            'rounding_minutes' => ['required', Rule::in([1, 5, 10, 15, 30])],
            'weekend_days' => ['required', 'array', 'min:1'],
            'weekend_days.*' => ['integer', 'between:0,6'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
