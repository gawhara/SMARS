@extends('layouts.app')

@section('title', $employee->localizedName())

@php
    $rows = fn ($pairs) => collect($pairs)->filter(fn ($v) => $v !== null && $v !== '');
    $salaryFields = ['basic_salary', 'overtime', 'housing_allowance', 'transportation_allowance', 'other_allowances', 'training_labor_wages', 'previous_dues', 'total', 'total_deductions', 'remaining_salary'];
@endphp

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.emp.profile') }}</span>
            <h1>{{ $employee->localizedName() }}</h1>
            <p>{{ $employee->job_title ?: __('app.none') }}</p>
        </div>
        <div class="table-actions">
            <a class="ghost-button" href="{{ route('employees.edit', $employee) }}">{{ __('app.edit') }}</a>
            <a class="ghost-button" href="{{ route('employees.index') }}">{{ __('app.cancel') }}</a>
        </div>
    </section>

    @include('partials.flash')

    <section class="panel profile-header">
        <span class="avatar avatar-lg">{{ $employee->initials() }}</span>
        <div class="profile-badges">
            <span class="status-badge {{ $employee->status === 'active' ? 'success' : 'muted' }}">{{ $employee->status === 'active' ? __('app.active') : __('app.inactive') }}</span>
            <span class="status-badge {{ $employee->isSaudi() ? 'info' : 'muted' }}">{{ $employee->isSaudi() ? __('app.emp.saudi') : __('app.emp.non_saudi') }}</span>
            <span class="status-badge muted">{{ __('app.emp.hr_employee_id') }}: {{ $employee->hr_employee_id }}</span>
            <span class="status-badge muted">{{ $employee->company?->localizedName() }}</span>
        </div>
    </section>

    <section class="panel">
        <div class="tabs" data-tabs>
            <div class="tab-nav">
                <button type="button" class="tab-link active" data-tab="overview">{{ __('app.emp.tab_overview') }}</button>
                <button type="button" class="tab-link" data-tab="personal">{{ __('app.emp.tab_personal') }}</button>
                <button type="button" class="tab-link" data-tab="job">{{ __('app.emp.tab_job') }}</button>
                <button type="button" class="tab-link" data-tab="salary">{{ __('app.emp.tab_salary') }}</button>
                <button type="button" class="tab-link" data-tab="documents">{{ __('app.emp.tab_documents') }}</button>
                <button type="button" class="tab-link" data-tab="attendance">{{ __('app.emp.tab_attendance') }}</button>
                <button type="button" class="tab-link" data-tab="leaves">{{ __('app.emp.tab_leaves') }}</button>
                <button type="button" class="tab-link" data-tab="audit">{{ __('app.emp.tab_audit') }}</button>
            </div>

            <div class="tab-panel active" data-panel="overview">
                <dl class="detail-list">
                    <div><dt>{{ __('app.name_en') }}</dt><dd>{{ $employee->name_en }}</dd></div>
                    <div><dt>{{ __('app.emp.employee_code') }}</dt><dd>{{ $employee->employee_code }}</dd></div>
                    <div><dt>{{ __('app.emp.financial_employee_id') }}</dt><dd>{{ $employee->financial_employee_id ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company') }}</dt><dd>{{ $employee->company?->localizedName() }}</dd></div>
                    <div><dt>{{ __('app.branch') }}</dt><dd>{{ $employee->orgBranch?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.department') }}</dt><dd>{{ $employee->department?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.position') }}</dt><dd>{{ $employee->position?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.shift') }}</dt><dd>{{ $employee->shift?->localizedName() ?: __('app.none') }}</dd></div>
                </dl>
            </div>

            <div class="tab-panel" data-panel="personal">
                <dl class="detail-list">
                    <div><dt>{{ __('app.emp.national_id') }}</dt><dd>{{ $employee->national_id }}</dd></div>
                    <div><dt>{{ __('app.emp.nationality') }}</dt><dd>{{ $country?->localizedName() ?: $employee->nationality }}</dd></div>
                    <div><dt>{{ __('app.emp.saudi_non_saudi') }}</dt><dd>{{ $employee->isSaudi() ? __('app.emp.saudi') : __('app.emp.non_saudi') }}</dd></div>
                    <div><dt>{{ __('app.emp.gender') }}</dt><dd>{{ $employee->gender ? __('app.emp.'.$employee->gender) : __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.birth_date') }}</dt><dd>{{ optional($employee->birth_date)->format('Y-m-d') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.iqama_expiry') }}</dt><dd>{{ optional($employee->iqama_expiry)->format('Y-m-d') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.passport_id') }}</dt><dd>{{ $employee->passport_id }}</dd></div>
                    <div><dt>{{ __('app.emp.passport_expiry') }}</dt><dd>{{ optional($employee->passport_expiry)->format('Y-m-d') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.email') }}</dt><dd>{{ $employee->email ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.phone') }}</dt><dd>{{ $employee->phone ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.phone_2') }}</dt><dd>{{ $employee->phone_2 ?: __('app.none') }}</dd></div>
                </dl>
            </div>

            <div class="tab-panel" data-panel="job">
                <dl class="detail-list">
                    <div><dt>{{ __('app.emp.job_title') }}</dt><dd>{{ $employee->job_title ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.contract_type') }}</dt><dd>{{ $employee->contract_type ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.start_date') }}</dt><dd>{{ optional($employee->start_date)->format('Y-m-d') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.end_date') }}</dt><dd>{{ optional($employee->end_date)->format('Y-m-d') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.employment_status') }}</dt><dd>{{ $employee->employment_status ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.bank') }}</dt><dd>{{ $bank?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.iban') }}</dt><dd>{{ $employee->iban ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.bank_branch') }}</dt><dd>{{ $employee->branch ?: __('app.none') }}</dd></div>
                </dl>
            </div>

            <div class="tab-panel" data-panel="salary">
                <dl class="detail-list">
                    @foreach ($salaryFields as $field)
                        <div><dt>{{ __('app.emp.'.$field) }}</dt><dd>{{ number_format((float) $employee->$field, 2) }}</dd></div>
                    @endforeach
                </dl>
            </div>

            <div class="tab-panel" data-panel="documents"><p class="muted-note">{{ __('app.emp.tab_placeholder') }}</p></div>
            <div class="tab-panel" data-panel="attendance"><p class="muted-note">{{ __('app.emp.tab_placeholder') }}</p></div>
            <div class="tab-panel" data-panel="leaves"><p class="muted-note">{{ __('app.emp.tab_placeholder') }}</p></div>

            <div class="tab-panel" data-panel="audit">
                <dl class="detail-list">
                    <div><dt>{{ __('app.created_by') }}</dt><dd>{{ $employee->creator?->name ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.created_at') }}</dt><dd>{{ optional($employee->created_at)->format('Y-m-d H:i') }}</dd></div>
                    <div><dt>{{ __('app.updated_by') }}</dt><dd>{{ $employee->updater?->name ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.updated_at') }}</dt><dd>{{ optional($employee->updated_at)->format('Y-m-d H:i') }}</dd></div>
                </dl>
            </div>
        </div>
    </section>

    <script>
        (function () {
            const container = document.querySelector('[data-tabs]');
            if (!container) return;
            container.querySelectorAll('.tab-link').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const target = btn.getAttribute('data-tab');
                    container.querySelectorAll('.tab-link').forEach(b => b.classList.toggle('active', b === btn));
                    container.querySelectorAll('.tab-panel').forEach(function (panel) {
                        panel.classList.toggle('active', panel.getAttribute('data-panel') === target);
                    });
                });
            });
        })();
    </script>
@endsection
