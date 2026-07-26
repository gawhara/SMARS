@extends('layouts.app')

@section('title', __('app.biometric_devices'))

@php
    $tone = ['online' => 'success', 'unreachable' => 'danger', 'sync_failed' => 'danger', 'offline' => 'muted', 'inactive' => 'muted', 'unknown' => 'warning'];
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.biometric_devices') }}</h1>
        </div>
        @can('devices.manage')
            <div class="table-actions">
                @if ($devices->total() > 0)
                    <form method="POST" action="{{ route('devices.sync-all') }}" onsubmit="return confirm('{{ __('app.device.confirm_sync_all') }}')">
                        @csrf
                        <button class="ghost-button" type="submit">{{ __('app.device.read_all') }}</button>
                    </form>
                @endif
                <a class="primary-button" href="{{ route('devices.create') }}">{{ __('app.device.add') }}</a>
            </div>
        @endcan
    </section>

    @include('partials.flash')

    <div class="mini-stats">
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.device.stat_total') }}</span>
            <strong class="mini-stat-value">{{ number_format($stats['total']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.device.stat_active') }}</span>
            <strong class="mini-stat-value tone-info">{{ number_format($stats['active']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.device.stat_online') }}</span>
            <strong class="mini-stat-value tone-success">{{ number_format($stats['online']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.device.stat_unreachable') }}</span>
            <strong class="mini-stat-value {{ $stats['unreachable'] > 0 ? 'tone-warning' : '' }}">{{ number_format($stats['unreachable']) }}</strong>
        </div>
    </div>

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search') }}">
            <select name="company_id">
                <option value="">{{ __('app.all_companies') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>
                @endforeach
            </select>
            <select name="connection_type">
                <option value="">{{ __('app.device.connection_type') }}</option>
                @foreach (['lan', 'vpn', 'ddns', 'static_ip'] as $type)
                    <option value="{{ $type }}" @selected(request('connection_type') === $type)>{{ __('app.device.connection_'.$type) }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">{{ __('app.all_statuses') }}</option>
                @foreach (['online', 'unreachable', 'offline', 'unknown'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ __('app.device.status_'.$st) }}</option>
                @endforeach
            </select>
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    @if ($devices->count())
        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.device.device_name') }}</th>
                            <th>{{ __('app.status') }}</th>
                            <th>{{ __('app.device.connection_type') }}</th>
                            <th>{{ __('app.device.target') }}</th>
                            <th>{{ __('app.company') }}</th>
                            <th>{{ __('app.device.last_success') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $device)
                            @php $st = $device->displayStatus(); @endphp
                            <tr>
                                <td>
                                    <div class="att-name-cell">
                                        <span class="device-icon">@include('partials.icon', ['name' => 'fingerprint', 'class' => 'device-icon-svg'])</span>
                                        <div>
                                            <a class="cell-name" href="{{ route('devices.punches', $device) }}">{{ $device->device_name }}</a>
                                            <small>{{ $device->device_model }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge {{ $tone[$st] ?? 'muted' }}">
                                        <span class="status-dot" aria-hidden="true"></span>{{ __('app.device.status_'.$st) }}
                                    </span>
                                </td>
                                <td>{{ __('app.device.connection_'.$device->connection_type) }}</td>
                                <td dir="ltr">{{ $device->connectionTarget() }}</td>
                                <td>{{ $device->company?->localizedName() ?? __('app.none') }}</td>
                                <td><bdi dir="ltr">{{ optional($device->last_successful_connection_at)->format('Y-m-d H:i') ?? __('app.device.never') }}</bdi></td>
                                <td class="table-actions">
                                    @can('devices.manage')
                                        <form method="POST" action="{{ route('devices.sync', $device) }}">
                                            @csrf
                                            <button class="ghost-button" type="submit">{{ __('app.device.sync_now_readonly') }}</button>
                                        </form>
                                        <a class="ghost-button" href="{{ route('devices.edit', $device) }}">{{ __('app.edit') }}</a>
                                        <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="danger-button" type="submit">{{ __('app.delete') }}</button>
                                        </form>
                                    @else
                                        <a class="ghost-button" href="{{ route('devices.punches', $device) }}">{{ __('app.view') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <div class="company-grid-pagination">{{ $devices->links() }}</div>
    @else
        <section class="panel empty-state">
            <span class="empty-icon">@include('partials.icon', ['name' => 'fingerprint', 'class' => 'empty-icon-svg'])</span>
            <h3>{{ __('app.device.title') }}</h3>
            <p>{{ __('app.device.intro') }}</p>
            <div class="empty-actions">
                <a class="primary-button" href="{{ route('devices.create') }}">{{ __('app.device.add') }}</a>
            </div>
        </section>
    @endif
@endsection
