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
        <a class="primary-button" href="{{ route('devices.create') }}">{{ __('app.device.add') }}</a>
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
        <div class="device-grid">
            @foreach ($devices as $device)
                @php $st = $device->displayStatus(); @endphp
                <article class="device-card status-{{ $st }}">
                    <div class="device-card-top">
                        <span class="device-icon">@include('partials.icon', ['name' => 'fingerprint', 'class' => 'device-icon-svg'])</span>
                        <div class="device-card-title">
                            <a href="{{ route('devices.show', $device) }}">{{ $device->device_name }}</a>
                            <small>{{ $device->device_model }}</small>
                        </div>
                        <span class="status-badge {{ $tone[$st] ?? 'muted' }}">
                            <span class="status-dot" aria-hidden="true"></span>{{ __('app.device.status_'.$st) }}
                        </span>
                    </div>

                    <dl class="device-meta">
                        <div>
                            <dt>{{ __('app.device.connection_type') }}</dt>
                            <dd>{{ __('app.device.connection_'.$device->connection_type) }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('app.device.target') }}</dt>
                            <dd dir="ltr">{{ $device->connectionTarget() }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('app.company') }}</dt>
                            <dd>{{ $device->company?->localizedName() ?? __('app.none') }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('app.device.last_success') }}</dt>
                            <dd>{{ optional($device->last_successful_connection_at)->format('Y-m-d H:i') ?? __('app.device.never') }}</dd>
                        </div>
                    </dl>

                    <div class="device-card-actions">
                        <form method="POST" action="{{ route('devices.test', $device) }}">
                            @csrf
                            <button class="primary-button" type="submit">{{ __('app.device.test_connection') }}</button>
                        </form>
                        <a class="ghost-button" href="{{ route('devices.edit', $device) }}">{{ __('app.edit') }}</a>
                        <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="danger-button" type="submit">{{ __('app.delete') }}</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
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
