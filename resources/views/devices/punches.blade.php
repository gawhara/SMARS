@extends('layouts.app')

@section('title', __('app.device.raw_punches').' — '.$device->device_name)

@php
    $match = request('match');
    $typeTone = ['in' => 'success', 'out' => 'info'];
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ $device->device_name }}</span>
            <h1>{{ __('app.device.raw_punches') }}</h1>
            <p>{{ __('app.device.raw_intro') }}</p>
        </div>
        <div class="table-actions">
            @can('devices.manage')
                <form method="POST" action="{{ route('devices.sync', $device) }}">
                    @csrf
                    <button class="primary-button" type="submit">{{ __('app.device.sync_now_readonly') }}</button>
                </form>
                <form method="POST" action="{{ route('devices.test', $device) }}">
                    @csrf
                    <button class="ghost-button" type="submit">{{ __('app.device.test_connection') }}</button>
                </form>
            @endcan
            <a class="ghost-button" href="{{ route('devices.show', $device) }}">{{ __('app.device.manage_device') }}</a>
            <a class="ghost-button" href="{{ route('devices.index') }}">{{ __('app.device.back_to_devices') }}</a>
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
            <span class="mini-stat-label">{{ __('app.device.last_punch') }}</span>
            <strong class="mini-stat-value" style="font-size:1.1rem"><bdi dir="ltr">{{ optional($stats['last'] ? \Carbon\Carbon::parse($stats['last']) : null)->format('Y-m-d H:i') ?? __('app.none') }}</bdi></strong>
        </div>
    </div>

    <div class="match-tabs">
        <a class="match-tab {{ ! $match ? 'active' : '' }}" href="{{ route('devices.punches', [$device, 'search' => request('search')]) }}">{{ __('app.att.all_records') }}</a>
        <a class="match-tab {{ $match === 'matched' ? 'active' : '' }}" href="{{ route('devices.punches', [$device, 'match' => 'matched']) }}">{{ __('app.att.matched') }}</a>
        <a class="match-tab {{ $match === 'unmatched' ? 'active' : '' }}" href="{{ route('devices.punches', [$device, 'match' => 'unmatched']) }}">{{ __('app.att.unmatched') }} ({{ $stats['unmatched'] }})</a>
    </div>

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            @if ($match)<input type="hidden" name="match" value="{{ $match }}">@endif
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.att.search_employee') }}">
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
                            <th>{{ __('app.enroll.device_user_id') }}</th>
                            <th>{{ __('app.device.punch_time') }}</th>
                            <th>{{ __('app.device.punch_type') }}</th>
                            <th>{{ __('app.device.raw_code') }}</th>
                            <th>{{ __('app.device.verification') }}</th>
                            <th>{{ __('app.device.source') }}</th>
                            <th>{{ __('app.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                <td>
                                    @if ($record->employee)
                                        <a class="cell-name" href="{{ route('attendance.employee', $record->employee) }}">{{ $record->employee->localizedName() }}</a>
                                    @else
                                        <span class="muted-note">{{ __('app.att.unmatched') }}</span>
                                    @endif
                                </td>
                                <td dir="ltr">{{ $record->device_user_id }}</td>
                                <td><bdi dir="ltr">{{ $record->punch_at->format('d/m/Y H:i:s') }}</bdi></td>
                                <td><span class="status-badge {{ $typeTone[$record->punch_type] ?? 'muted' }}">{{ __('app.att.punch_'.$record->punch_type) }}</span></td>
                                <td dir="ltr"><code class="raw-code">{{ $record->raw_punch_type ?? '—' }}</code></td>
                                <td dir="ltr"><code class="raw-code">{{ $record->verification_type !== null && $record->verification_type !== '' ? $record->verification_type : '—' }}</code></td>
                                <td dir="ltr">{{ $record->source ?: __('app.none') }}</td>
                                <td>
                                    <span class="status-badge {{ $record->isMatched() ? 'success' : 'warning' }}">
                                        {{ $record->isMatched() ? __('app.att.matched') : __('app.att.unmatched') }}
                                    </span>
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
            <h3>{{ __('app.device.no_punches') }}</h3>
            <p>{{ __('app.device.no_punches_hint') }}</p>
        </section>
    @endif
@endsection
