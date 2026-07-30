<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'legal_name_ar' => ['nullable', 'string', 'max:255'],
            'legal_name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:60', Rule::unique('companies', 'code')->ignore($company)],
            // Saudi Commercial Registration = 10 digits; VAT = 15 digits.
            'cr_number' => ['nullable', 'digits:10', Rule::unique('companies', 'cr_number')->ignore($company)],
            'vat_number' => ['nullable', 'digits:15', Rule::unique('companies', 'vat_number')->ignore($company)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'established_date' => ['nullable', 'date', 'before_or_equal:today'],
            'is_active' => ['nullable', 'boolean'],
            // Wage Protection System (Mudad) establishment details.
            'wps_establishment_id' => ['nullable', 'string', 'max:40'],
            'employer_bank_code' => ['nullable', 'string', 'max:4'],
            'employer_iban' => ['nullable', 'string', 'max:34'],
        ];
    }
}
