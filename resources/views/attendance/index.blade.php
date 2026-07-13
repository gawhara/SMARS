@extends('layouts.app')

@section('title', __('app.attendance'))

@php
    $match = request('match');
    $formatPunchTime = static function ($value): string {
        if (! $value) return '—';
        $time = $value instanceof \Carbon\Carbon ? $value : \Carbon\Carbon::parse($value);
        return app()->isLocale('ar')
            ? $time->format('h:i').' '.($time->format('A') === 'AM' ? __('app.time_am') : __('app.time_pm'))
            : $time->format('h:i A');
    };
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.att.punch_log') }}</h1>
        </div>
        <div class="table-actions">
            <a class="ghost-button" href="{{ route('attendance.holidays.index') }}">{{ __('app.att.holidays') }}</a>
            <a class="ghost-button" href="{{ route('attendance.leaves.index') }}">{{ __('app.att.leaves') }}</a>
            <a class="ghost-button" href="{{ route('attendance.policies.index') }}">{{ __('app.att.policies') }}</a>
            <a class="ghost-button" href="{{ route('attendance.corrections.index') }}">{{ __('app.att.corrections') }}</a>
            <a class="ghost-button" href="{{ route('attendance.daily') }}">{{ __('app.att.open_daily') }}</a>
            <a class="ghost-button" href="{{ route('attendance.exceptions') }}">{{ __('app.att.open_exceptions') }}</a>
            <a class="ghost-button" href="{{ route('attendance.index', array_merge(request()->query(), ['export' => 'csv'])) }}">{{ __('app.att.export_csv') }}</a>
            <a class="ghost-button" href="{{ route('attendance.report') }}">{{ __('app.att.open_report') }}</a>
            <a class="ghost-button" href="{{ route('attendance.matrix') }}">{{ __('app.att.open_matrix') }}</a>
            <a class="ghost-button" href="{{ route('attendance.import.form') }}">{{ __('app.att.import') }}</a>
            <a class="primary-button" href="{{ route('attendance.create') }}">{{ __('app.att.add_manual') }}</a>
        </div>
    </section>

    @include('partials.flash')

    <div class="mini-stats">
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.att.stat_total') }}</span>
            <strong class="mini-stat-value">{{ number_format($stats['total']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.att.stat_matched') }}</span>
            <strong class="mini-stat-value tone-success">{{ number_format($stats['matched']) }}</strong>
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

    <div class="match-tabs">
        <a class="match-tab {{ ! $match ? 'active' : '' }}" href="{{ route('attendance.index') }}">{{ __('app.att.all_records') }}</a>
        <a class="match-tab {{ $match === 'matched' ? 'active' : '' }}" href="{{ route('attendance.index', ['match' => 'matched']) }}">{{ __('app.att.matched') }}</a>
        <a class="match-tab {{ $match === 'unmatched' ? 'active' : '' }}" href="{{ route('attendance.index', ['match' => 'unmatched']) }}">{{ __('app.att.unmatched') }} ({{ $stats['unmatched'] }})</a>
    </div>

    @if ($match === 'unmatched' && $records->total() > 0)
        <div class="alert alert-danger">{{ __('app.att.unmatched_hint') }}</div>
    @endif

    <section class="panel filter-panel attendance-filter-panel">
        <form class="filter-bar attendance-filter-bar" method="GET">
            @if ($match)<input type="hidden" name="match" value="{{ $match }}">@endif
            <label class="attendance-filter-field attendance-filter-search">
                <span>{{ __('app.search') }}</span>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search') }}">
            </label>
            <label class="attendance-filter-field">
                <span>{{ __('app.att.employee') }}</span>
                <select name="employee_id">
                    <option value="">{{ __('app.att.all_employees') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->localizedName() }} · {{ $employee->hr_employee_id }}</option>
                    @endforeach
                </select>
            </label>
            <label class="attendance-filter-field">
                <span>{{ __('app.att.machine') }}</span>
                <select name="machine_id">
                    <option value="">{{ __('app.att.machine') }}</option>
                    @foreach ($machines as $machine)
                        <option value="{{ $machine->id }}" @selected((string) request('machine_id') === (string) $machine->id)>{{ $machine->device_name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="attendance-filter-field attendance-filter-range">
                <span>{{ __('app.att.date_from') }} — {{ __('app.att.date_to') }}</span>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="{{ __('app.att.date_from') }}">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="{{ __('app.att.date_to') }}">
                </div>
            </div>
            <div class="attendance-filter-field attendance-filter-range attendance-filter-time">
                <span>{{ __('app.att.time_from') }} — {{ __('app.att.time_to') }}</span>
                <div>
                    <input type="time" name="time_from" value="{{ request('time_from') }}" aria-label="{{ __('app.att.time_from') }}" title="{{ __('app.att.time_from') }}">
                    <input type="time" name="time_to" value="{{ request('time_to') }}" aria-label="{{ __('app.att.time_to') }}" title="{{ __('app.att.time_to') }}">
                </div>
            </div>
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    @if ($dailyRows->count())
        <section class="panel">
            <div class="table-wrap">
                <table class="attendance-day-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.att.employee') }}</th>
                            <th>{{ __('app.att.date') }}</th>
                            <th>{{ __('app.att.period_1_entry') }}</th>
                            <th>{{ __('app.att.period_1_exit') }}</th>
                            <th>{{ __('app.att.period_2_entry') }}</th>
                            <th>{{ __('app.att.period_2_exit') }}</th>
                            <th>{{ __('app.att.actual_scheduled') }}</th>
                            <th>{{ __('app.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dailyRows as $summary)
                            @php
                                $periods = collect($summary->matched_periods);
                                $periodOne = $periods->firstWhere('number', 1);
                                $periodTwo = $periods->firstWhere('number', 2);
                            @endphp
                            <tr>
                                <td>
                                    <a class="cell-name" href="{{ route('employees.show', $summary->employee) }}">{{ $summary->employee->localizedName() }}</a>
                                    <small>{{ $summary->employee->hr_employee_id }} · {{ $summary->employee->company?->localizedName() }}</small>
                                </td>
                                <td dir="ltr"><strong>{{ $summary->attendance_date->format('Y-m-d') }}</strong><small>{{ $summary->punch_count }} {{ __('app.att.punches') }}</small></td>
                                @foreach ([[$periodOne, 'actual_in', 'scheduled_in'], [$periodOne, 'actual_out', 'scheduled_out'], [$periodTwo, 'actual_in', 'scheduled_in'], [$periodTwo, 'actual_out', 'scheduled_out']] as [$period, $actualKey, $scheduledKey])
                                    <td>
                                        @if ($period)
                                            <div class="shift-punch-match {{ empty($period[$actualKey]) ? 'missing' : '' }}">
                                                <strong dir="ltr">{{ $formatPunchTime($period[$actualKey] ?? null) }}</strong>
                                                <small>{{ __('app.att.scheduled_time') }}: <span dir="ltr">{{ $formatPunchTime($period[$scheduledKey] ?? null) }}</span></small>
                                            </div>
                                        @else
                                            <span class="period-not-applicable">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td>
                                    <strong>{{ number_format($summary->worked_minutes / 60, 2) }}</strong>
                                    <small>/ {{ number_format($summary->scheduled_minutes / 60, 2) }} {{ __('app.att.hours') }}</small>
                                </td>
                                <td><span class="status-badge {{ $summary->has_exception ? 'warning' : ($summary->status === 'late' ? 'info' : 'success') }}">{{ __('app.att.summary_'.$summary->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <div class="company-grid-pagination">{{ $dailyRows->links() }}</div>
    @else
        <section class="panel empty-state">
            <span class="empty-icon">@include('partials.icon', ['name' => 'clock', 'class' => 'empty-icon-svg'])</span>
            <h3>{{ __('app.att.empty_title') }}</h3>
            <p>{{ __('app.att.empty_hint') }}</p>
            <div class="empty-actions">
                <a class="primary-button" href="{{ route('attendance.create') }}">{{ __('app.att.add_manual') }}</a>
                <a class="ghost-button" href="{{ route('attendance.import.form') }}">{{ __('app.att.import') }}</a>
            </div>
        </section>
    @endif
@endsection
