@extends('layouts.app')

@section('title', __('app.att.report_title'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.att.report_title') }}</h1>
            <p>{{ __('app.att.report_range', ['from' => $from->format('d/m/Y'), 'to' => $to->format('d/m/Y')]) }}</p>
        </div>
        <div class="table-actions">
            <a class="primary-button" href="{{ route('attendance.report', array_merge(request()->query(), ['export' => 'csv'])) }}">{{ __('app.att.export_csv') }}</a>
            <a class="ghost-button" href="{{ route('attendance.index') }}">{{ __('app.att.back_to_log') }}</a>
        </div>
    </section>

    @include('partials.flash')

    <div class="mini-stats">
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.att.st_present') }}</span>
            <strong class="mini-stat-value tone-success">{{ number_format($totals['present']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.att.st_late') }}</span>
            <strong class="mini-stat-value tone-warning">{{ number_format($totals['late']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.att.st_absent') }}</span>
            <strong class="mini-stat-value {{ $totals['absent'] > 0 ? 'text-danger' : '' }}">{{ number_format($totals['absent']) }}</strong>
        </div>
        <div class="mini-stat accent">
            <span class="mini-stat-label">{{ __('app.att.hours') }}</span>
            <strong class="mini-stat-value">{{ number_format($totals['hours'], 1) }}</strong>
        </div>
    </div>

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" aria-label="{{ __('app.att.date_from') }}">
            <input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" aria-label="{{ __('app.att.date_to') }}">
            <select name="company_id">
                <option value="">{{ __('app.all_companies') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>
                @endforeach
            </select>
            <select name="department_id">
                <option value="">{{ __('app.all_departments') }}</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->localizedName() }}</option>
                @endforeach
            </select>
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.att.employee') }}</th>
                        <th>{{ __('app.company') }}</th>
                        <th>{{ __('app.att.st_present') }}</th>
                        <th>{{ __('app.att.st_late') }}</th>
                        <th>{{ __('app.att.st_absent') }}</th>
                        <th>{{ __('app.att.st_rest') }}</th>
                        <th>{{ __('app.att.st_holiday') }}</th>
                        <th>{{ __('app.att.st_leave') }}</th>
                        <th>{{ __('app.att.worked_days') }}</th>
                        <th>{{ __('app.att.punches') }}</th>
                        <th>{{ __('app.att.hours') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $employee = $row['employee']; @endphp
                        <tr>
                            <td>
                                <a class="cell-name" href="{{ route('employees.show', $employee) }}">{{ $employee->localizedName() }}</a>
                                <small>{{ $employee->employee_code }}</small>
                            </td>
                            <td>{{ $employee->company?->localizedName() ?? __('app.none') }}</td>
                            <td><span class="tone-success">{{ $row['present'] }}</span></td>
                            <td><span class="tone-warning">{{ $row['late'] }}</span></td>
                            <td><span class="{{ $row['absent'] > 0 ? 'text-danger' : '' }}">{{ $row['absent'] }}</span></td>
                            <td>{{ $row['rest'] }}</td>
                            <td>{{ $row['holiday'] }}</td>
                            <td>{{ $row['leave'] }}</td>
                            <td><strong>{{ $row['worked_days'] }}</strong></td>
                            <td>{{ $row['punches'] }}</td>
                            <td><strong>{{ number_format($row['hours'], 1) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="empty-row">{{ __('app.att.no_employees') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
