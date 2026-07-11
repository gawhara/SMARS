@extends('layouts.app')

@section('title', __('app.attendance'))

@php
    $typeTone = ['in' => 'success', 'out' => 'info', 'unknown' => 'muted'];
    $match = request('match');
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.att.punch_log') }}</h1>
        </div>
        <div class="table-actions">
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

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            @if ($match)<input type="hidden" name="match" value="{{ $match }}">@endif
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search') }}">
            <select name="company_id">
                <option value="">{{ __('app.all_companies') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>
                @endforeach
            </select>
            <select name="machine_id">
                <option value="">{{ __('app.att.machine') }}</option>
                @foreach ($machines as $machine)
                    <option value="{{ $machine->id }}" @selected((string) request('machine_id') === (string) $machine->id)>{{ $machine->device_name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="{{ __('app.att.date_from') }}">
            <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="{{ __('app.att.date_to') }}">
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    @if ($records->count())
        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.att.employee') }}</th>
                            <th>{{ __('app.att.punch_at') }}</th>
                            <th>{{ __('app.att.punch_type') }}</th>
                            <th>{{ __('app.att.machine') }}</th>
                            <th>{{ __('app.att.source') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr class="{{ $record->isMatched() ? '' : 'row-unmatched' }}">
                                <td>
                                    @if ($record->isMatched())
                                        <a class="cell-name" href="{{ route('employees.show', $record->employee) }}">{{ $record->employee->localizedName() }}</a>
                                        <small>{{ $record->company?->localizedName() }}</small>
                                    @else
                                        <span class="cell-name">{{ $record->device_user_id ?: __('app.none') }}</span>
                                        <small><span class="status-badge warning">{{ __('app.att.unmatched_badge') }}</span></small>
                                    @endif
                                </td>
                                <td dir="ltr">{{ $record->punch_at->format('Y-m-d H:i') }}</td>
                                <td><span class="status-badge {{ $typeTone[$record->punch_type] ?? 'muted' }}">{{ __('app.att.punch_'.$record->punch_type) }}</span></td>
                                <td>{{ $record->machine?->device_name ?? __('app.none') }}</td>
                                <td>{{ __('app.att.source_'.$record->source) }}</td>
                                <td class="table-actions">
                                    <form method="POST" action="{{ route('attendance.destroy', $record) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
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
        <div class="company-grid-pagination">{{ $records->links() }}</div>
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
