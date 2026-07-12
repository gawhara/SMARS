@extends('layouts.app')

@section('title', __('app.att.daily_title'))

@section('content')
    <section class="page-heading compact">
        <div><span class="eyebrow">{{ __('app.attendance') }}</span><h1>{{ __('app.att.daily_title') }}</h1><p>{{ __('app.att.daily_intro') }}</p></div>
        <div class="table-actions"><a class="ghost-button" href="{{ route('attendance.exceptions') }}">{{ __('app.att.open_exceptions') }}</a><a class="ghost-button" href="{{ route('attendance.index') }}">{{ __('app.att.back_to_log') }}</a></div>
    </section>

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <select name="company_id"><option value="">{{ __('app.all_companies') }}</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>@endforeach</select>
            <select name="employee_id"><option value="">{{ __('app.att.all_employees') }}</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->localizedName() }} · {{ $employee->employee_code }}</option>@endforeach</select>
            <select name="status"><option value="">{{ __('app.all_statuses') }}</option>@foreach(['present','late','half_day','incomplete'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ __('app.att.summary_'.$status) }}</option>@endforeach</select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="{{ __('app.att.date_from') }}">
            <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="{{ __('app.att.date_to') }}">
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header"><div><h2>{{ __('app.att.daily_title') }}</h2><p>{{ $summaries->total() }} {{ __('app.att.daily_records') }}</p></div></div>
        <div class="table-wrap"><table>
            <thead><tr><th>{{ __('app.att.employee') }}</th><th>{{ __('app.att.date') }}</th><th>{{ __('app.att.first_in') }}</th><th>{{ __('app.att.last_out') }}</th><th>{{ __('app.att.actual_scheduled') }}</th><th>{{ __('app.att.late_minutes') }}</th><th>{{ __('app.att.early_minutes') }}</th><th>{{ __('app.att.overtime_minutes') }}</th><th>{{ __('app.status') }}</th></tr></thead>
            <tbody>@forelse($summaries as $summary)<tr>
                <td><a class="cell-name" href="{{ route('employees.show', $summary->employee) }}">{{ $summary->employee->localizedName() }}</a><small>{{ $summary->employee->employee_code }} · {{ $summary->employee->company?->localizedName() }}</small></td>
                <td>{{ $summary->attendance_date->format('Y-m-d') }}</td><td dir="ltr">{{ $summary->localizedTime('first_in_at') }}</td><td dir="ltr">{{ $summary->localizedTime('last_out_at') }}</td>
                <td><strong>{{ number_format($summary->worked_minutes / 60, 2) }}</strong> / {{ number_format($summary->scheduled_minutes / 60, 2) }}</td>
                <td>{{ $summary->late_minutes }}</td><td>{{ $summary->early_leave_minutes }}</td><td>{{ $summary->overtime_minutes }}</td>
                <td><span class="status-badge {{ $summary->has_exception ? 'warning' : ($summary->status === 'late' ? 'info' : 'success') }}">{{ __('app.att.summary_'.$summary->status) }}</span></td>
            </tr>@empty<tr><td colspan="9" class="empty-row">{{ __('app.att.no_daily') }}</td></tr>@endforelse</tbody>
        </table></div>{{ $summaries->links() }}
    </section>
@endsection
