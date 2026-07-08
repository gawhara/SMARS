<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $position = $this->route('position');

        return [
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('positions', 'name_ar')->ignore($position)],
            'name_en' => ['required', 'string', 'max:255', Rule::unique('positions', 'name_en')->ignore($position)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
