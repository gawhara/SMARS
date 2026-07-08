<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $department = $this->route('department');

        return [
            'name_ar' => ['required', 'string', 'max:255', Rule::unique('departments', 'name_ar')->ignore($department)],
            'name_en' => ['required', 'string', 'max:255', Rule::unique('departments', 'name_en')->ignore($department)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
