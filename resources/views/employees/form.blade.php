@extends('layouts.app')

@section('title', $employee->exists ? __('app.edit') : __('app.emp.add'))

@php
    $salaryFields = ['basic_salary', 'overtime', 'housing_allowance', 'transportation_allowance', 'other_allowances', 'training_labor_wages', 'previous_dues', 'total'];
    $gosiFields = ['basic_salary_gosi', 'housing_allowance_gosi', 'other_gosi_items', 'diff_registered_housing_allowance'];
    $deductionFields = ['absence_deduction', 'delay_deduction', 'leave_deduction', 'warnings_penalties', 'insurance_deduction', 'loans', 'social_insurance_saudi', 'total_deductions'];
    $payoutFields = ['cash', 'al_rajhi_transfer', 'bank_albilad_transfer', 'riyad_bank_transfer', 'remaining_salary'];
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.employees') }}</span>
            <h1>{{ $employee->exists ? __('app.edit') : __('app.emp.add') }}</h1>
            <p>{{ __('app.central_hr_note') }}</p>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel form-panel">
        <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}" id="employee-form">
            @csrf
            @if ($employee->exists)
                @method('PUT')
            @endif

            {{-- Identity & identifiers --}}
            <h3 class="form-section-title">{{ __('app.emp.section_identity') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.name_ar') }} *</span>
                    <input name="name_ar" value="{{ old('name_ar', $employee->name_ar) }}" required>
                    @error('name_ar')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.name_en') }} *</span>
                    <input name="name_en" value="{{ old('name_en', $employee->name_en) }}" required>
                    @error('name_en')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.employee_code') }} *</span>
                    <input name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" required>
                    @error('employee_code')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.hr_employee_id') }} *</span>
                    <input name="hr_employee_id" value="{{ old('hr_employee_id', $employee->hr_employee_id) }}" required>
                    @error('hr_employee_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.financial_employee_id') }}</span>
                    <input name="financial_employee_id" value="{{ old('financial_employee_id', $employee->financial_employee_id) }}">
                    @error('financial_employee_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.national_id') }} *</span>
                    <input name="national_id" id="national_id" value="{{ old('national_id', $employee->national_id) }}" maxlength="10" inputmode="numeric" required>
                    @error('national_id')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            {{-- Organization --}}
            <h3 class="form-section-title">{{ __('app.emp.section_organization') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.company') }} *</span>
                    <select name="company_id" id="company_id" required>
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) old('company_id', $employee->company_id) === (string) $company->id)>{{ $company->localizedName() }}</option>
                        @endforeach
                    </select>
                    @error('company_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.branch') }}</span>
                    <select name="branch_id" id="branch_id" data-selected="{{ old('branch_id', $employee->branch_id) }}">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" data-company="{{ $branch->company_id }}" @selected((string) old('branch_id', $employee->branch_id) === (string) $branch->id)>{{ $branch->localizedName() }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.department') }}</span>
                    <select name="department_id">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('department_id', $employee->department_id) === (string) $department->id)>{{ $department->localizedName() }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>{{ __('app.position') }}</span>
                    <select name="position_id">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) old('position_id', $employee->position_id) === (string) $position->id)>{{ $position->localizedName() }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>{{ __('app.emp.shift') }}</span>
                    <select name="shift_id">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($shifts as $shift)
                            <option value="{{ $shift->id }}" @selected((string) old('shift_id', $employee->shift_id) === (string) $shift->id)>{{ $shift->localizedScheduleLabel() }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>{{ __('app.status') }}</span>
                    <select name="status">
                        <option value="active" @selected(old('status', $employee->status) === 'active')>{{ __('app.active') }}</option>
                        <option value="inactive" @selected(old('status', $employee->status) === 'inactive')>{{ __('app.inactive') }}</option>
                    </select>
                </label>
            </div>

            {{-- Personal & documents --}}
            <h3 class="form-section-title">{{ __('app.emp.section_personal') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.emp.saudi_non_saudi') }} *</span>
                    <select name="saudi_non_saudi" id="saudi_non_saudi" required>
                        <option value="saudi" @selected(old('saudi_non_saudi', $employee->saudi_non_saudi) === 'saudi')>{{ __('app.emp.saudi') }}</option>
                        <option value="non_saudi" @selected(old('saudi_non_saudi', $employee->saudi_non_saudi) === 'non_saudi')>{{ __('app.emp.non_saudi') }}</option>
                    </select>
                    @error('saudi_non_saudi')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.nationality') }} *</span>
                    <select name="nationality" id="nationality" required>
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->iso2 }}" @selected(old('nationality', $employee->nationality) === $country->iso2)>{{ $country->localizedName() }}</option>
                        @endforeach
                    </select>
                    @error('nationality')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.gender') }}</span>
                    <select name="gender">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        <option value="male" @selected(old('gender', $employee->gender) === 'male')>{{ __('app.emp.male') }}</option>
                        <option value="female" @selected(old('gender', $employee->gender) === 'female')>{{ __('app.emp.female') }}</option>
                    </select>
                </label>
                <label>
                    <span>{{ __('app.emp.birth_date') }}</span>
                    <input type="date" name="birth_date" value="{{ old('birth_date', optional($employee->birth_date)->format('Y-m-d')) }}">
                    @error('birth_date')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.iqama_expiry') }}</span>
                    <input type="date" name="iqama_expiry" min="{{ now()->toDateString() }}" value="{{ old('iqama_expiry', optional($employee->iqama_expiry)->format('Y-m-d')) }}">
                    @error('iqama_expiry')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.passport_id') }} *</span>
                    <input name="passport_id" value="{{ old('passport_id', $employee->passport_id) }}" required>
                    @error('passport_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.passport_expiry') }}</span>
                    <input type="date" name="passport_expiry" min="{{ now()->toDateString() }}" value="{{ old('passport_expiry', optional($employee->passport_expiry)->format('Y-m-d')) }}">
                    @error('passport_expiry')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.full_name_arabic') }}</span>
                    <input name="full_name_arabic" value="{{ old('full_name_arabic', $employee->full_name_arabic) }}">
                </label>
                <label>
                    <span>{{ __('app.emp.full_name_english') }}</span>
                    <input name="full_name_english" value="{{ old('full_name_english', $employee->full_name_english) }}">
                </label>
                <label>
                    <span>{{ __('app.emp.iqama_full_name_arabic') }}</span>
                    <input name="iqama_full_name_arabic" value="{{ old('iqama_full_name_arabic', $employee->iqama_full_name_arabic) }}">
                </label>
                <label>
                    <span>{{ __('app.emp.iqama_full_name_english') }}</span>
                    <input name="iqama_full_name_english" value="{{ old('iqama_full_name_english', $employee->iqama_full_name_english) }}">
                </label>
                <label>
                    <span>{{ __('app.emp.passport_full_name_arabic') }}</span>
                    <input name="passport_full_name_arabic" value="{{ old('passport_full_name_arabic', $employee->passport_full_name_arabic) }}">
                </label>
                <label>
                    <span>{{ __('app.emp.passport_full_name_english') }}</span>
                    <input name="passport_full_name_english" value="{{ old('passport_full_name_english', $employee->passport_full_name_english) }}">
                </label>
            </div>

            {{-- Contact --}}
            <h3 class="form-section-title">{{ __('app.emp.section_contact') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.email') }}</span>
                    <input type="text" name="email" value="{{ old('email', $employee->email) }}" placeholder="name@example.com">
                    @error('email')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.phone') }}</span>
                    <input name="phone" value="{{ old('phone', $employee->phone) }}" placeholder="+9665XXXXXXXX">
                    @error('phone')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.phone_2') }}</span>
                    <input name="phone_2" value="{{ old('phone_2', $employee->phone_2) }}" placeholder="+9665XXXXXXXX">
                    @error('phone_2')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            {{-- Job & contract --}}
            <h3 class="form-section-title">{{ __('app.emp.section_job') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.emp.job_title') }}</span>
                    @php $jobTitle = old('job_title', $employee->job_title); @endphp
                    <select name="job_title">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->name_ar }}" @selected($jobTitle === $position->name_ar)>{{ $position->name_ar }}</option>
                        @endforeach
                        @if ($jobTitle && ! $positions->contains('name_ar', $jobTitle))
                            <option value="{{ $jobTitle }}" selected>{{ $jobTitle }}</option>
                        @endif
                    </select>
                    @error('job_title')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.contract_type') }}</span>
                    <input name="contract_type" value="{{ old('contract_type', $employee->contract_type) }}">
                </label>
                <label>
                    <span>{{ __('app.emp.start_date') }}</span>
                    <input type="date" name="start_date" value="{{ old('start_date', optional($employee->start_date)->format('Y-m-d')) }}">
                </label>
                <label>
                    <span>{{ __('app.emp.end_date') }}</span>
                    <input type="date" name="end_date" value="{{ old('end_date', optional($employee->end_date)->format('Y-m-d')) }}">
                    @error('end_date')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.employment_status') }}</span>
                    <input name="employment_status" value="{{ old('employment_status', $employee->employment_status) }}">
                </label>
            </div>

            {{-- Banking --}}
            <h3 class="form-section-title">{{ __('app.emp.section_bank') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.emp.bank') }}</span>
                    <select name="bank">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->code }}" @selected(old('bank', $employee->bank) === $bank->code)>{{ $bank->localizedName() }}</option>
                        @endforeach
                    </select>
                    @error('bank')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.iban') }}</span>
                    <input name="iban" value="{{ old('iban', $employee->iban) }}" placeholder="SA...">
                    @error('iban')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.emp.bank_branch') }}</span>
                    <input name="branch" value="{{ old('branch', $employee->branch) }}">
                </label>
            </div>

            {{-- Salary components --}}
            <h3 class="form-section-title">{{ __('app.emp.section_salary') }}</h3>
            <div class="form-grid">
                @foreach ($salaryFields as $field)
                    <label>
                        <span>{{ __('app.emp.'.$field) }}</span>
                        <input type="number" step="0.01" min="0" name="{{ $field }}" value="{{ old($field, $employee->$field ?? 0) }}">
                        @error($field)<small>{{ $message }}</small>@enderror
                    </label>
                @endforeach
            </div>

            {{-- GOSI --}}
            <h3 class="form-section-title">{{ __('app.emp.section_gosi') }}</h3>
            <div class="form-grid">
                @foreach ($gosiFields as $field)
                    <label>
                        <span>{{ __('app.emp.'.$field) }}</span>
                        <input type="number" step="0.01" min="0" name="{{ $field }}" value="{{ old($field, $employee->$field ?? 0) }}">
                    </label>
                @endforeach
            </div>

            {{-- Deductions --}}
            <h3 class="form-section-title">{{ __('app.emp.section_deductions') }}</h3>
            <div class="form-grid">
                @foreach ($deductionFields as $field)
                    <label>
                        <span>{{ __('app.emp.'.$field) }}</span>
                        <input type="number" step="0.01" min="0" name="{{ $field }}" value="{{ old($field, $employee->$field ?? 0) }}">
                    </label>
                @endforeach
            </div>

            {{-- Payout --}}
            <h3 class="form-section-title">{{ __('app.emp.section_payout') }}</h3>
            <div class="form-grid">
                @foreach ($payoutFields as $field)
                    <label>
                        <span>{{ __('app.emp.'.$field) }}</span>
                        <input type="number" step="0.01" min="0" name="{{ $field }}" value="{{ old($field, $employee->$field ?? 0) }}">
                    </label>
                @endforeach
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit">{{ __('app.save') }}</button>
                <a class="ghost-button" href="{{ route('employees.index') }}">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </section>

    <script>
        (function () {
            const nationalId = document.getElementById('national_id');
            const saudiFlag = document.getElementById('saudi_non_saudi');
            const nationality = document.getElementById('nationality');
            const company = document.getElementById('company_id');
            const branch = document.getElementById('branch_id');

            // National ID auto-logic (CODEX §12): 1 => Saudi, 2 => Non-Saudi.
            function applyNationalIdLogic() {
                const first = (nationalId.value || '').charAt(0);
                if (first === '1') {
                    saudiFlag.value = 'saudi';
                    nationality.value = 'SA';
                    saudiFlag.setAttribute('disabled', 'disabled');
                } else if (first === '2') {
                    saudiFlag.value = 'non_saudi';
                    saudiFlag.removeAttribute('disabled');
                } else {
                    saudiFlag.removeAttribute('disabled');
                }
            }

            // Re-enable disabled selects on submit so their value is posted.
            document.getElementById('employee-form').addEventListener('submit', function () {
                saudiFlag.removeAttribute('disabled');
            });

            // Branch dropdown shows only branches of the selected company (CODEX §9).
            function filterBranches() {
                const selectedCompany = company.value;
                const preselected = branch.getAttribute('data-selected');
                Array.from(branch.options).forEach(function (option) {
                    if (!option.value) return;
                    const match = option.getAttribute('data-company') === selectedCompany;
                    option.hidden = !match;
                    if (!match && option.selected) {
                        branch.value = '';
                    }
                });
                if (preselected && branch.value === '') {
                    const opt = branch.querySelector('option[value="' + preselected + '"]');
                    if (opt && !opt.hidden) branch.value = preselected;
                }
            }

            nationalId.addEventListener('input', applyNationalIdLogic);
            company.addEventListener('change', filterBranches);
            applyNationalIdLogic();
            filterBranches();
        })();
    </script>
@endsection
