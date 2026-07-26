@extends('layouts.app')

@section('title', __('app.att.directory_title'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.att.directory_title') }}</h1>
            <p>{{ __('app.att.directory_intro') }}</p>
        </div>
        @can('attendance.manage')
            <div class="table-actions">
                <a class="ghost-button" href="{{ route('attendance.import.form') }}">{{ __('app.att.import') }}</a>
                <a class="primary-button" href="{{ route('attendance.create') }}">{{ __('app.att.add_manual') }}</a>
            </div>
        @endcan
    </section>

    @include('partials.flash')

    <div class="mini-stats">
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.att.stat_employees') }}</span>
            <strong class="mini-stat-value">{{ number_format($stats['employees']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.att.stat_total') }}</span>
            <strong class="mini-stat-value">{{ number_format($stats['total']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.att.stat_unmatched') }}</span>
            <strong class="mini-stat-value {{ $stats['unmatched'] > 0 ? 'tone-warning' : '' }}">{{ number_format($stats['unmatched']) }}</strong>
        </div>
        <div class="mini-stat accent">
            <span class="mini-stat-label">{{ __('app.att.stat_today') }}</span>
            <strong class="mini-stat-value">{{ number_format($stats['today']) }}</strong>
        </div>
    </div>

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.att.search_employee') }}">
            <select name="company_id">
                <option value="">{{ __('app.all_companies') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>
                @endforeach
            </select>
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    @if ($employees->count())
        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.att.employee') }}</th>
                            <th>{{ __('app.enroll.device_user_id') }}</th>
                            <th>{{ __('app.company') }}</th>
                            <th>{{ __('app.department') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            <tr>
                                <td>
                                    <div class="att-name-cell">
                                        <span class="att-avatar">{{ mb_substr($employee->localizedName(), 0, 1) }}</span>
                                        <div>
                                            <a class="cell-name" href="{{ route('attendance.employee', $employee) }}">{{ $employee->localizedName() }}</a>
                                            <small>{{ $employee->employee_code }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td dir="ltr">{{ $employee->hr_employee_id ?: __('app.none') }}</td>
                                <td>{{ $employee->company?->localizedName() ?? __('app.none') }}</td>
                                <td>{{ $employee->department?->localizedName() ?? __('app.none') }}</td>
                                <td class="table-actions">
                                    <a class="ghost-button" href="{{ route('attendance.employee', $employee) }}">{{ __('app.att.view_attendance') }}</a>
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
            <h3>{{ __('app.att.no_employees') }}</h3>
        </section>
    @endif
@endsection
