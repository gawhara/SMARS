<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branch = $this->route('branch');
        $companyId = $this->integer('company_id');

        return [
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'name_ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'name_ar')->where('company_id', $companyId)->ignore($branch),
            ],
            'name_en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'name_en')->where('company_id', $companyId)->ignore($branch),
            ],
            'location' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
