@extends('layouts.app')

@section('title', $device->device_name)

@php
    $tone = ['online' => 'success', 'unreachable' => 'danger', 'sync_failed' => 'danger', 'offline' => 'muted', 'inactive' => 'muted', 'unknown' => 'warning'];
    $st = $device->displayStatus();
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.device.profile') }}</span>
            <h1>{{ $device->device_name }}</h1>
        </div>
        <div class="table-actions">
            <form method="POST" action="{{ route('devices.test', $device) }}">
                @csrf
                <button class="primary-button" type="submit">{{ __('app.device.test_connection') }}</button>
            </form>
            <a class="ghost-button" href="{{ route('devices.edit', $device) }}">{{ __('app.edit') }}</a>
            <a class="ghost-button" href="{{ route('devices.index') }}">{{ __('app.cancel') }}</a>
        </div>
    </section>

    @include('partials.flash')

    <section class="panel profile-header">
        <span class="company-mark company-mark-lg company-mark-soft">@include('partials.icon', ['name' => 'fingerprint', 'class' => 'empty-icon-svg'])</span>
        <div class="profile-badges">
            <span class="status-badge {{ $tone[$st] ?? 'muted' }}">{{ __('app.device.status_'.$st) }}</span>
            <span class="status-badge muted">{{ $device->device_model }}</span>
            <span class="status-badge muted" dir="ltr">{{ $device->connectionTarget() }}</span>
        </div>
    </section>

    <div class="detail-columns">
        <section class="panel">
            <div class="panel-header"><h2>{{ __('app.device.section_connection') }}</h2></div>
            <div class="panel-body">
                <dl class="detail-list">
                    <div><dt>{{ __('app.device.connection_type') }}</dt><dd>{{ __('app.device.connection_'.$device->connection_type) }}</dd></div>
                    <div><dt>{{ __('app.device.target') }}</dt><dd dir="ltr">{{ $device->connectionTarget() }}</dd></div>
                    <div><dt>{{ __('app.device.port') }}</dt><dd dir="ltr">{{ $device->port }}</dd></div>
                    <div><dt>{{ __('app.device.serial_number') }}</dt><dd>{{ $device->serial_number ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.device.username') }}</dt><dd dir="ltr">{{ $device->username ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.device.timezone') }}</dt><dd dir="ltr">{{ $device->timezone }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h2>{{ __('app.device.section_location') }}</h2></div>
            <div class="panel-body">
                <dl class="detail-list">
                    <div><dt>{{ __('app.company') }}</dt><dd>{{ $device->company?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.branch') }}</dt><dd>{{ $device->branch?->localizedName() ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.device.location_description') }}</dt><dd>{{ $device->location_description ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.device.last_success') }}</dt><dd>{{ optional($device->last_successful_connection_at)->format('Y-m-d H:i') ?: __('app.device.never') }}</dd></div>
                    <div><dt>{{ __('app.device.last_failed') }}</dt><dd>{{ optional($device->last_failed_connection_at)->format('Y-m-d H:i') ?: __('app.device.never') }}</dd></div>
                    <div><dt>{{ __('app.device.last_sync') }}</dt><dd>{{ optional($device->last_sync_at)->format('Y-m-d H:i') ?: __('app.device.never') }}</dd></div>
                </dl>
            </div>
        </section>
    </div>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>{{ __('app.enroll.title') }}</h2>
                <p>{{ $device->enrollments_count }} {{ __('app.enroll.count') }}</p>
            </div>
            <a class="primary-button" href="{{ route('devices.enrollments.index', $device) }}">{{ __('app.enroll.manage') }}</a>
        </div>
        <div class="panel-body">
            <p class="muted-note" style="margin:0">{{ __('app.enroll.intro') }}</p>
        </div>
    </section>

    @if ($device->notes)
        <section class="panel">
            <div class="panel-header"><h2>{{ __('app.device.notes') }}</h2></div>
            <div class="panel-body"><p class="muted-note">{{ $device->notes }}</p></div>
        </section>
    @endif
@endsection
