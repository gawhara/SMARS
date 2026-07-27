@extends('layouts.app')

@section('title', $employee->localizedName())

@php
    $rows = fn ($pairs) => collect($pairs)->filter(fn ($v) => $v !== null && $v !== '');
    $salaryFields = ['basic_salary', 'overtime', 'housing_allowance', 'transportation_allowance', 'other_allowances', 'training_labor_wages', 'previous_dues', 'total', 'total_deductions', 'remaining_salary'];
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.emp.profile') }}</span>
            <h1>{{ $employee->localizedName() }}</h1>
            <p>{{ $employee->job_title ?: __('app.none') }}</p>
        </div>
        <div class="table-actions">
            <button type="button" class="primary-button" onclick="window.print()">{{ __('app.emp.print_file') }}</button>
            @can('employees.manage')
                <a class="ghost-button" href="{{ route('employees.edit', $employee) }}">{{ __('app.edit') }}</a>
            @endcan
            <a class="ghost-button" href="{{ route('employees.index') }}">{{ __('app.cancel') }}</a>
        </div>
    </section>

    @include('partials.flash')

    <section class="panel profile-header">
        <span class="avatar avatar-lg">{{ $employee->initials() }}</span>
        <div class="profile-identity">
            <span class="profile-kicker">{{ $employee->employee_code }}</span>
            <h2>{{ $employee->localizedName() }}</h2>
            <p>{{ $employee->job_title ?: __('app.none') }} · {{ $employee->department?->localizedName() ?: __('app.none') }}</p>
            <div class="profile-badges">
                <span class="status-badge {{ $employee->status === 'active' ? 'success' : 'muted' }}">{{ $employee->status === 'active' ? __('app.active') : __('app.inactive') }}</span>
                <span class="status-badge {{ $employee->isSaudi() ? 'info' : 'muted' }}">{{ $employee->isSaudi() ? __('app.emp.saudi') : __('app.emp.non_saudi') }}</span>
                <span class="status-badge muted">{{ __('app.emp.hr_employee_id') }}: {{ $employee->hr_employee_id }}</span>
                <span class="status-badge muted">{{ $employee->company?->localizedName() }}</span>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="tabs" data-tabs>
            <div class="tab-nav" role="tablist" aria-label="{{ __('app.emp.profile') }}">
                <button type="button" class="tab-link active" data-tab="overview" role="tab" aria-selected="true">{{ __('app.emp.tab_overview') }}</button>
                <button type="button" class="tab-link" data-tab="personal" role="tab" aria-selected="false">{{ __('app.emp.tab_personal') }}</button>
                <button type="button" class="tab-link" data-tab="job" role="tab" aria-selected="false">{{ __('app.emp.tab_job') }}</button>
                <button type="button" class="tab-link" data-tab="salary" role="tab" aria-selected="false">{{ __('app.emp.tab_salary') }}</button>
                <button type="button" class="tab-link" data-tab="documents" role="tab" aria-selected="false">{{ __('app.emp.tab_documents') }}</button>
                <button type="button" class="tab-link" data-tab="attendance" role="tab" aria-selected="false">{{ __('app.emp.tab_attendance') }}</button>
                <button type="button" class="tab-link" data-tab="leaves" role="tab" aria-selected="false">{{ __('app.emp.tab_leaves') }}</button>
                <button type="button" class="tab-link" data-tab="penalties" role="tab" aria-selected="false">{{ __('app.penalty.in_employee_record') }}</button>
            </div>

            <div class="tab-panel active" data-panel="overview">
                <div class="tab-panel-heading"><div><span>{{ __('app.emp.profile') }}</span><h2>{{ __('app.emp.tab_overview') }}</h2></div><p>{{ __('app.emp.overview_hint') }}</p></div>
                <dl class="detail-list">
                    <div><dt>{{ __('app.name_en') }}</dt><dd>{{ $employee->name_en }}</dd></div>
                    <div><dt>{{ __('app.emp.employee_code') }}</dt><dd>{{ $employee->employee_code }}</dd></div>
                    <div><dt>{{ __('app.emp.financial_employee_id') }}</dt><dd>{{ $employee->financial_employee_id ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company') }}</dt><dd>{{ $employee->company?->localizedName() }}</dd></div>
                    <div><dt>{{ __('app.branch') }}</dt><dd>{{ $employee->orgBranch?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.department') }}</dt><dd>{{ $employee->department?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.position') }}</dt><dd>{{ $employee->position?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.shift') }}</dt><dd>{{ $employee->shift?->localizedScheduleLabel() ?: __('app.none') }}</dd></div>
                </dl>
            </div>

            <div class="tab-panel" data-panel="personal">
                <div class="tab-panel-heading"><div><span>{{ __('app.emp.section_identity') }}</span><h2>{{ __('app.emp.tab_personal') }}</h2></div><p>{{ __('app.emp.personal_hint') }}</p></div>
                <dl class="detail-list">
                    <div><dt>{{ __('app.emp.national_id') }}</dt><dd>{{ $employee->national_id }}</dd></div>
                    <div><dt>{{ __('app.emp.nationality') }}</dt><dd>{{ $country?->localizedName() ?: $employee->nationality }}</dd></div>
                    <div><dt>{{ __('app.emp.saudi_non_saudi') }}</dt><dd>{{ $employee->isSaudi() ? __('app.emp.saudi') : __('app.emp.non_saudi') }}</dd></div>
                    <div><dt>{{ __('app.emp.gender') }}</dt><dd>{{ $employee->gender ? __('app.emp.'.$employee->gender) : __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.birth_date') }}</dt><dd>{{ optional($employee->birth_date)->format('d/m/Y') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.iqama_expiry') }}</dt><dd>{{ optional($employee->iqama_expiry)->format('d/m/Y') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.passport_id') }}</dt><dd>{{ $employee->passport_id }}</dd></div>
                    <div><dt>{{ __('app.emp.passport_expiry') }}</dt><dd>{{ optional($employee->passport_expiry)->format('d/m/Y') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.email') }}</dt><dd>{{ $employee->email ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.phone') }}</dt><dd>{{ $employee->phone ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.phone_2') }}</dt><dd>{{ $employee->phone_2 ?: __('app.none') }}</dd></div>
                </dl>
            </div>

            <div class="tab-panel" data-panel="job">
                <div class="tab-panel-heading"><div><span>{{ __('app.emp.section_organization') }}</span><h2>{{ __('app.emp.tab_job') }}</h2></div><p>{{ __('app.emp.job_hint') }}</p></div>
                <dl class="detail-list">
                    <div><dt>{{ __('app.emp.job_title') }}</dt><dd>{{ $employee->job_title ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.contract_type') }}</dt><dd>{{ $employee->contract_type ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.start_date') }}</dt><dd>{{ optional($employee->start_date)->format('d/m/Y') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.end_date') }}</dt><dd>{{ optional($employee->end_date)->format('d/m/Y') ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.employment_status') }}</dt><dd>{{ $employee->employment_status ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.bank') }}</dt><dd>{{ $bank?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.iban') }}</dt><dd>{{ $employee->iban ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.emp.bank_branch') }}</dt><dd>{{ $employee->branch ?: __('app.none') }}</dd></div>
                </dl>
            </div>

            <div class="tab-panel" data-panel="salary">
                <div class="tab-panel-heading"><div><span>{{ __('app.currency') }}</span><h2>{{ __('app.emp.tab_salary') }}</h2></div><p>{{ __('app.emp.salary_hint') }}</p></div>
                <div class="salary-highlight">
                    <span>{{ __('app.emp.remaining_salary') }}</span>
                    <strong>{{ number_format((float) $employee->remaining_salary, 2) }}</strong>
                    <small>{{ __('app.currency') }}</small>
                </div>
                <dl class="detail-list">
                    @foreach ($salaryFields as $field)
                        <div><dt>{{ __('app.emp.'.$field) }}</dt><dd class="money-value">{{ number_format((float) $employee->$field, 2) }} <small>{{ __('app.currency') }}</small></dd></div>
                    @endforeach
                </dl>
            </div>

            <div class="tab-panel" data-panel="documents"><div class="profile-empty-state"><strong>{{ __('app.emp.tab_documents') }}</strong><p>{{ __('app.emp.tab_placeholder') }}</p></div></div>
            <div class="tab-panel" data-panel="attendance">
                <div class="tab-panel-heading"><div><span>{{ __('app.attendance') }}</span><h2>{{ __('app.emp.tab_attendance') }}</h2></div><p>{{ __('app.att.period_range', ['from' => $attendanceMonth->format('d/m/Y'), 'to' => now()->format('d/m/Y')]) }}</p></div>
                @if ($attendanceSummary)
                    <div class="mini-stats">
                        <div class="mini-stat"><span class="mini-stat-label">{{ __('app.att.present_days') }}</span><strong class="mini-stat-value tone-success">{{ $attendanceSummary['present'] }}</strong></div>
                        <div class="mini-stat"><span class="mini-stat-label">{{ __('app.att.late_days') }}</span><strong class="mini-stat-value tone-warning">{{ $attendanceSummary['late'] }}</strong></div>
                        <div class="mini-stat"><span class="mini-stat-label">{{ __('app.att.absent_days') }}</span><strong class="mini-stat-value {{ $attendanceSummary['absent'] > 0 ? 'text-danger' : '' }}">{{ $attendanceSummary['absent'] }}</strong></div>
                        <div class="mini-stat"><span class="mini-stat-label">{{ __('app.att.leave_days') }}</span><strong class="mini-stat-value">{{ $attendanceSummary['leave'] }}</strong></div>
                        <div class="mini-stat"><span class="mini-stat-label">{{ __('app.att.holiday_days') }}</span><strong class="mini-stat-value">{{ $attendanceSummary['holiday'] }}</strong></div>
                        <div class="mini-stat accent"><span class="mini-stat-label">{{ __('app.att.worked_hours') }}</span><strong class="mini-stat-value">{{ number_format($attendanceSummary['hours'], 1) }}</strong></div>
                    </div>
                    <p class="muted-note" style="margin-top:12px"><a href="{{ route('attendance.employee', $employee) }}">{{ __('app.att.employee_attendance') }} →</a></p>
                @else
                    <div class="profile-empty-state"><strong>{{ __('app.emp.tab_attendance') }}</strong><p>{{ __('app.att.no_days') }}</p></div>
                @endif
            </div>

            <div class="tab-panel" data-panel="leaves">
                <div class="tab-panel-heading"><div><span>{{ __('app.leaves') }}</span><h2>{{ __('app.emp.tab_leaves') }}</h2></div></div>
                @if (($leaves ?? collect())->isNotEmpty())
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('app.att.date_from') }}</th>
                                    <th>{{ __('app.att.date_to') }}</th>
                                    <th>{{ __('app.att.leave_days') }}</th>
                                    <th>{{ __('app.att.leave_type') }}</th>
                                    <th>{{ __('app.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leaves as $leave)
                                    <tr>
                                        <td><bdi dir="ltr">{{ $leave->start_date->format('d/m/Y') }}</bdi></td>
                                        <td><bdi dir="ltr">{{ $leave->end_date->format('d/m/Y') }}</bdi></td>
                                        <td>{{ $leave->start_date->diffInDays($leave->end_date) + 1 }}</td>
                                        <td>{{ $leave->leave_type ?: __('app.none') }}</td>
                                        <td><span class="status-badge {{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning') }}">{{ __('app.att.leave_status_'.$leave->status) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="profile-empty-state"><strong>{{ __('app.emp.tab_leaves') }}</strong><p>{{ __('app.none') }}</p></div>
                @endif
            </div>

            <div class="tab-panel" data-panel="penalties">
                @if (($penalties ?? collect())->isNotEmpty())
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('app.penalty.date') }}</th>
                                    <th>{{ __('app.penalty.type') }}</th>
                                    <th>{{ __('app.penalty.reason') }}</th>
                                    <th>{{ __('app.penalty.amount') }}</th>
                                    <th>{{ __('app.penalty.status') }}</th>
                                    <th>{{ __('app.penalty.created_by') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($penalties as $p)
                                    <tr>
                                        <td><bdi dir="ltr">{{ $p->penalty_date->format('d/m/Y') }}</bdi></td>
                                        <td><span class="status-badge info">{{ __('app.penalty.type_'.$p->type) }}</span></td>
                                        <td>{{ $p->reason }}</td>
                                        <td dir="ltr"><strong class="{{ $p->amount > 0 && $p->isActive() ? 'text-danger' : '' }}">{{ number_format((float) $p->amount, 2) }}</strong></td>
                                        <td><span class="status-badge {{ $p->isActive() ? 'danger' : 'muted' }}">{{ __('app.penalty.status_'.$p->status) }}</span></td>
                                        <td>{{ $p->creator?->name ?? __('app.none') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @can('penalties.view')
                        <p class="muted-note" style="margin-top:12px"><a href="{{ route('penalties.index', ['employee_id' => $employee->id]) }}">{{ __('app.penalty.title') }} →</a></p>
                    @endcan
                @else
                    <div class="profile-empty-state"><strong>{{ __('app.penalty.in_employee_record') }}</strong><p>{{ __('app.penalty.no_penalties') }}</p></div>
                @endif
            </div>

        </div>
    </section>

    <script>
        (function () {
            const container = document.querySelector('[data-tabs]');
            if (!container) return;
            // Label each panel with its tab name so printing shows section headings.
            container.querySelectorAll('.tab-panel').forEach(function (panel) {
                const link = container.querySelector('.tab-link[data-tab="' + panel.getAttribute('data-panel') + '"]');
                if (link) panel.setAttribute('data-title', link.textContent.trim());
            });
            container.querySelectorAll('.tab-link').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const target = btn.getAttribute('data-tab');
                    container.querySelectorAll('.tab-link').forEach(function (b) {
                        const active = b === btn;
                        b.classList.toggle('active', active);
                        b.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    container.querySelectorAll('.tab-panel').forEach(function (panel) {
                        panel.classList.toggle('active', panel.getAttribute('data-panel') === target);
                    });
                });
            });
        })();
    </script>
@endsection
