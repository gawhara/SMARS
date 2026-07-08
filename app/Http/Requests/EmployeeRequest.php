<?php

namespace App\Http\Requests;

use App\Models\Bank;
use App\Rules\SaudiIban;
use App\Support\SaudiPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize input before validation (CODEX §12, §14, §15, §17).
     */
    protected function prepareForValidation(): void
    {
        $nationalId = trim((string) $this->input('national_id'));

        $data = [
            'national_id' => $nationalId,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'phone' => SaudiPhone::normalize($this->input('phone')),
            'phone_2' => SaudiPhone::normalize($this->input('phone_2')),
            'iban' => $this->filled('iban')
                ? strtoupper(str_replace(' ', '', (string) $this->input('iban')))
                : null,
        ];

        // Auto-classification from the ID's first digit (also enforced by rules()).
        if (str_starts_with($nationalId, '1')) {
            $data['saudi_non_saudi'] = 'saudi';
            $data['nationality'] = 'SA';
        } elseif (str_starts_with($nationalId, '2')) {
            $data['saudi_non_saudi'] = 'non_saudi';
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $companyId = $this->integer('company_id');
        $expectedBankCode = $this->filled('bank')
            ? Bank::where('code', $this->input('bank'))->value('iban_code')
            : null;

        return [
            // Organization
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $companyId)->whereNull('deleted_at')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')->whereNull('deleted_at')],
            'shift_id' => ['nullable', 'integer', Rule::exists('shifts', 'id')->whereNull('deleted_at')],

            // Identity
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],

            // Identifiers (global uniqueness)
            'employee_code' => ['required', 'string', 'max:60', Rule::unique('employees', 'employee_code')->ignore($employee)],
            'financial_employee_id' => ['nullable', 'string', 'max:60', Rule::unique('employees', 'financial_employee_id')->ignore($employee)],
            'hr_employee_id' => ['required', 'string', 'max:60', Rule::unique('employees', 'hr_employee_id')->ignore($employee)],
            'national_id' => [
                'required',
                'digits:10',
                'regex:/^[12]\d{9}$/',
                Rule::unique('employees', 'national_id')->ignore($employee),
            ],

            // Contact (CODEX §14-15)
            'email' => [
                'nullable',
                'email:rfc',
                'regex:/^[A-Za-z0-9._%+\-@]+$/',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employee),
            ],
            'phone' => [
                'nullable',
                'regex:/^\+9665\d{8}$/',
                'different:phone_2',
                Rule::unique('employees', 'phone')->ignore($employee),
            ],
            'phone_2' => [
                'nullable',
                'regex:/^\+9665\d{8}$/',
                Rule::unique('employees', 'phone_2')->ignore($employee),
            ],

            // Personal
            'saudi_non_saudi' => ['required', Rule::in(['saudi', 'non_saudi'])],
            'nationality' => ['required', 'string', 'size:2', Rule::exists('countries', 'iso2')],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date', 'before:today'],

            // Official document names
            'iqama_full_name_arabic' => ['nullable', 'string', 'max:255'],
            'iqama_full_name_english' => ['nullable', 'string', 'max:255'],
            'full_name_arabic' => ['nullable', 'string', 'max:255'],
            'full_name_english' => ['nullable', 'string', 'max:255'],
            'passport_full_name_arabic' => ['nullable', 'string', 'max:255'],
            'passport_full_name_english' => ['nullable', 'string', 'max:255'],

            // Passport (CODEX §16) + expiry not in past (§18)
            'passport_id' => ['required', 'string', 'max:60', Rule::unique('employees', 'passport_id')->ignore($employee)],
            'iqama_expiry' => ['nullable', 'date', 'after_or_equal:today'],
            'passport_expiry' => ['nullable', 'date', 'after_or_equal:today'],

            // Job / contract
            'job_title' => ['nullable', 'string', 'max:255'],
            'contract_type' => ['nullable', 'string', 'max:60'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:today', 'after_or_equal:start_date'],

            // Banking (CODEX §17)
            'bank' => ['nullable', 'string', Rule::exists('banks', 'code')],
            'iban' => ['nullable', 'string', new SaudiIban($expectedBankCode ?: null)],
            'branch' => ['nullable', 'string', 'max:255'],

            // Status
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'employment_status' => ['nullable', 'string', 'max:60'],
        ] + $this->salaryRules();
    }

    /**
     * All monetary fields share the same numeric constraints.
     */
    private function salaryRules(): array
    {
        $fields = [
            'basic_salary', 'overtime', 'housing_allowance', 'other_allowances',
            'transportation_allowance', 'training_labor_wages', 'previous_dues', 'total',
            'basic_salary_gosi', 'housing_allowance_gosi', 'other_gosi_items',
            'diff_registered_housing_allowance', 'absence_deduction', 'delay_deduction',
            'leave_deduction', 'warnings_penalties', 'insurance_deduction', 'loans',
            'social_insurance_saudi', 'total_deductions', 'cash', 'al_rajhi_transfer',
            'bank_albilad_transfer', 'riyad_bank_transfer', 'remaining_salary',
        ];

        return array_fill_keys($fields, ['nullable', 'numeric', 'min:0', 'max:9999999999']);
    }

    public function attributes(): array
    {
        return [
            'name_ar' => __('app.name_ar'),
            'name_en' => __('app.name_en'),
            'national_id' => __('app.emp.national_id'),
            'passport_id' => __('app.emp.passport_id'),
            'hr_employee_id' => __('app.emp.hr_employee_id'),
            'employee_code' => __('app.emp.employee_code'),
            'phone' => __('app.emp.phone'),
            'phone_2' => __('app.emp.phone_2'),
            'iban' => __('app.emp.iban'),
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.regex' => __('app.emp.national_id_invalid'),
            'national_id.digits' => __('app.emp.national_id_invalid'),
            'phone.regex' => __('app.emp.phone_invalid'),
            'phone_2.regex' => __('app.emp.phone_invalid'),
            'phone.different' => __('app.emp.phone_duplicate_pair'),
            'email.regex' => __('app.emp.email_invalid'),
        ];
    }
}
