@extends('layouts.app')

@section('title', __('app.employees'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.organization_structure') }}</span>
            <h1>{{ __('app.employees') }}</h1>
        </div>
        <a class="primary-button" href="{{ route('employees.create') }}">{{ __('app.emp.add') }}</a>
    </section>

    @include('partials.flash')

    <div class="mini-stats">
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.emp.stat_total') }}</span>
            <strong class="mini-stat-value">{{ number_format($stats['total']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.emp.saudi') }}</span>
            <strong class="mini-stat-value tone-info">{{ number_format($stats['saudi']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.emp.non_saudi') }}</span>
            <strong class="mini-stat-value">{{ number_format($stats['non_saudi']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.emp.stat_active') }}</span>
            <strong class="mini-stat-value tone-success">{{ number_format($stats['active']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.emp.stat_iqama_expiring') }}</span>
            <strong class="mini-stat-value {{ $stats['iqama_expiring'] > 0 ? 'tone-warning' : '' }}">{{ number_format($stats['iqama_expiring']) }}</strong>
        </div>
        <div class="mini-stat accent">
            <span class="mini-stat-label">{{ __('app.emp.stat_payroll') }}</span>
            <strong class="mini-stat-value">{{ number_format($stats['payroll'], 0) }} <small>{{ __('app.currency') }}</small></strong>
        </div>
    </div>

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            {{-- Company is chosen from the sidebar; preserve it while other filters change. --}}
            @if (request('company_id'))
                <input type="hidden" name="company_id" value="{{ request('company_id') }}">
            @endif
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search') }}">
            <select name="department_id">
                <option value="">{{ __('app.all_departments') }}</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->localizedName() }}</option>
                @endforeach
            </select>
            <select name="saudi_non_saudi">
                <option value="">{{ __('app.all_nationalities') }}</option>
                <option value="saudi" @selected(request('saudi_non_saudi') === 'saudi')>{{ __('app.emp.saudi') }}</option>
                <option value="non_saudi" @selected(request('saudi_non_saudi') === 'non_saudi')>{{ __('app.emp.non_saudi') }}</option>
            </select>
            <select name="status">
                <option value="">{{ __('app.all_statuses') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('app.active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('app.inactive') }}</option>
            </select>
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    @php
        $current = request('company_id') ? $companies->firstWhere('id', (int) request('company_id')) : null;
        $hasFilters = request()->hasAny(['company_id', 'department_id', 'saudi_non_saudi', 'status', 'search']);
    @endphp

    <div class="list-toolbar">
        <div class="list-toolbar-left">
            <div class="list-count">
                <strong>{{ $employees->total() }}</strong>
                <span>{{ __('app.employees') }}</span>
            </div>
            @if ($current)
                <span class="context-chip">
                    @include('partials.company-mark', ['company' => $current, 'class' => 'company-mark-xs'])
                    {{ $current->localizedName() }}
                </span>
            @endif
        </div>
        @if ($hasFilters)
            <a class="chip-clear" href="{{ route('employees.index') }}">✕ {{ __('app.clear_filters') }}</a>
        @endif
    </div>

    @if ($employees->count())
        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.emp.name') }}</th>
                            <th>{{ __('app.emp.hr_employee_id') }}</th>
                            <th>{{ __('app.emp.job_title') }}</th>
                            <th>{{ __('app.emp.national_id') }}</th>
                            <th>{{ __('app.emp.contract_expiry') }}</th>
                            <th>{{ __('app.emp.saudi_non_saudi') }}</th>
                            <th>{{ __('app.status') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            <tr>
                                <td>
                                    <div class="cell-identity">
                                        <span class="avatar">{{ $employee->initials() }}</span>
                                        <div>
                                            <a class="cell-name" href="{{ route('employees.show', $employee) }}">{{ $employee->localizedName() }}</a>
                                            <small>{{ $employee->isSaudi() ? __('app.emp.saudi') : $employee->nationality }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $employee->hr_employee_id }}</td>
                                <td>{{ $employee->job_title ?: __('app.none') }}</td>
                                <td>{{ $employee->national_id }}</td>
                                <td>
                                    @php $end = $employee->end_date; @endphp
                                    @if ($end)
                                        <span class="{{ $end->isPast() ? 'text-danger' : ($end->lte(now()->addDays(30)) ? 'text-warning' : '') }}">{{ $end->format('Y-m-d') }}</span>
                                    @else
                                        {{ __('app.none') }}
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge {{ $employee->isSaudi() ? 'info' : 'muted' }}">
                                        {{ $employee->isSaudi() ? __('app.emp.saudi') : __('app.emp.non_saudi') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge {{ $employee->status === 'active' ? 'success' : 'muted' }}">
                                        {{ $employee->status === 'active' ? __('app.active') : __('app.inactive') }}
                                    </span>
                                </td>
                                <td class="table-actions">
                                    <a class="ghost-button" href="{{ route('employees.show', $employee) }}">{{ __('app.view') }}</a>
                                    <a class="ghost-button" href="{{ route('employees.edit', $employee) }}">{{ __('app.edit') }}</a>
                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger-button" type="submit">{{ __('app.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="company-grid-pagination">{{ $employees->links() }}</div>
    @else
        <section class="panel empty-state">
            <span class="empty-icon">@include('partials.icon', ['name' => 'users', 'class' => 'empty-icon-svg'])</span>
            <h3>{{ __('app.emp.empty_title') }}</h3>
            <p>{{ __('app.emp.empty_hint') }}</p>
            <div class="empty-actions">
                <a class="primary-button" href="{{ route('employees.create') }}">{{ __('app.emp.add') }}</a>
                @if ($hasFilters)
                    <a class="ghost-button" href="{{ route('employees.index') }}">{{ __('app.clear_filters') }}</a>
                @endif
            </div>
        </section>
    @endif
@endsection
