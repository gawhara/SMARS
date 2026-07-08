<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shift = $this->route('shift');

        return [
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('shifts', 'name_ar')->ignore($shift)],
            'name_en' => ['required', 'string', 'max:255', Rule::unique('shifts', 'name_en')->ignore($shift)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
