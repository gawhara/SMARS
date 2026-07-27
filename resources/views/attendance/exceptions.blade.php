@extends('layouts.app')

@section('title', __('app.att.exceptions_title'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.att.exceptions_title') }}</h1>
            <p>{{ __('app.att.exceptions_intro') }}</p>
        </div>
        <a class="ghost-button" href="{{ route('attendance.index') }}">{{ __('app.att.back_to_log') }}</a>
    </section>

    <div class="mini-stats">
        <div class="mini-stat"><span class="mini-stat-label">{{ __('app.att.exception_total') }}</span><strong class="mini-stat-value">{{ number_format($stats['total']) }}</strong></div>
        <div class="mini-stat"><span class="mini-stat-label">{{ __('app.att.ex_missing_in') }}</span><strong class="mini-stat-value tone-warning">{{ number_format($stats['missing_in']) }}</strong></div>
        <div class="mini-stat"><span class="mini-stat-label">{{ __('app.att.ex_missing_out') }}</span><strong class="mini-stat-value text-danger">{{ number_format($stats['missing_out']) }}</strong></div>
        <div class="mini-stat accent"><span class="mini-stat-label">{{ __('app.att.exception_today') }}</span><strong class="mini-stat-value">{{ number_format($stats['today']) }}</strong></div>
    </div>

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <select name="company_id"><option value="">{{ __('app.all_companies') }}</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>@endforeach</select>
            <select name="employee_id"><option value="">{{ __('app.att.all_employees') }}</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->localizedName() }} · {{ $employee->employee_code }}</option>@endforeach</select>
            <select name="exception"><option value="">{{ __('app.att.all_exceptions') }}</option>@foreach($exceptionTypes as $type)<option value="{{ $type }}" @selected(request('exception') === $type)>{{ __('app.att.ex_'.$type) }}</option>@endforeach</select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="{{ __('app.att.date_from') }}">
            <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="{{ __('app.att.date_to') }}">
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header"><div><h2>{{ __('app.att.exception_queue') }}</h2><p>{{ $summaries->total() }} {{ __('app.att.exception_records') }}</p></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>{{ __('app.att.employee') }}</th><th>{{ __('app.att.date') }}</th><th>{{ __('app.att.first_in') }}</th><th>{{ __('app.att.last_out') }}</th><th>{{ __('app.att.hours') }}</th><th>{{ __('app.att.exception_reason') }}</th><th>{{ __('app.actions') }}</th></tr></thead>
                <tbody>
                    @forelse($summaries as $summary)
                        <tr>
                            <td><a class="cell-name" href="{{ route('employees.show', $summary->employee) }}">{{ $summary->employee->localizedName() }}</a><small>{{ $summary->employee->employee_code }} · {{ $summary->employee->company?->localizedName() }}</small></td>
                            <td>{{ $summary->attendance_date->format('d/m/Y') }}</td>
                            <td dir="ltr">{{ $summary->localizedTime('first_in_at') }}</td>
                            <td dir="ltr">{{ $summary->localizedTime('last_out_at') }}</td>
                            <td><strong>{{ number_format($summary->worked_minutes / 60, 2) }}</strong></td>
                            <td><div class="exception-tags">@foreach($summary->exception_codes ?? [] as $code)<span class="status-badge warning">{{ __('app.att.ex_'.$code) }}</span>@endforeach</div></td>
                            <td class="table-actions"><a class="ghost-button" href="{{ route('attendance.index', ['employee_id' => $summary->employee_id, 'date_from' => $summary->attendance_date->format('Y-m-d'), 'date_to' => $summary->attendance_date->format('Y-m-d')]) }}">{{ __('app.att.review_punches') }}</a><a class="primary-button" href="{{ route('attendance.corrections.create', ['employee_id' => $summary->employee_id, 'date' => $summary->attendance_date->format('Y-m-d')]) }}">{{ __('app.att.add_correction') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-row">{{ __('app.att.no_exceptions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $summaries->links() }}
    </section>
@endsection
