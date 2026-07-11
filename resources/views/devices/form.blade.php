@extends('layouts.app')

@section('title', $device->exists ? __('app.edit') : __('app.device.add'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.biometric_devices') }}</span>
            <h1>{{ $device->exists ? __('app.edit') : __('app.device.add') }}</h1>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel form-panel">
        <form method="POST" action="{{ $device->exists ? route('devices.update', $device) : route('devices.store') }}" id="device-form">
            @csrf
            @if ($device->exists)
                @method('PUT')
            @endif

            <h3 class="form-section-title">{{ __('app.device.section_identity') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.device.device_name') }} *</span>
                    <input name="device_name" value="{{ old('device_name', $device->device_name) }}" required>
                    @error('device_name')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.device.device_model') }}</span>
                    <input name="device_model" value="{{ old('device_model', $device->device_model) }}">
                    @error('device_model')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.device.serial_number') }}</span>
                    <input name="serial_number" value="{{ old('serial_number', $device->serial_number) }}">
                    @error('serial_number')<small>{{ $message }}</small>@enderror
                </label>
                <label class="check-row">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $device->is_active ?? true))>
                    <span>{{ __('app.active') }}</span>
                </label>
            </div>

            <h3 class="form-section-title">{{ __('app.device.section_connection') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.device.connection_type') }} *</span>
                    <select name="connection_type" id="connection_type" required>
                        @foreach (['lan', 'vpn', 'static_ip', 'ddns'] as $type)
                            <option value="{{ $type }}" @selected(old('connection_type', $device->connection_type) === $type)>{{ __('app.device.connection_'.$type) }}</option>
                        @endforeach
                    </select>
                    @error('connection_type')<small>{{ $message }}</small>@enderror
                </label>
                <label data-conn="ip">
                    <span>{{ __('app.device.ip_address') }}</span>
                    <input name="ip_address" dir="ltr" value="{{ old('ip_address', $device->ip_address) }}" placeholder="192.168.1.100">
                    @error('ip_address')<small>{{ $message }}</small>@enderror
                </label>
                <label data-conn="domain" hidden>
                    <span>{{ __('app.device.domain') }}</span>
                    <input name="domain" dir="ltr" value="{{ old('domain', $device->domain) }}" placeholder="device.example.com">
                    @error('domain')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.device.port') }} *</span>
                    <input type="number" name="port" dir="ltr" min="1" max="65535" value="{{ old('port', $device->port ?? 4370) }}" required>
                    @error('port')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.device.username') }}</span>
                    <input name="username" dir="ltr" value="{{ old('username', $device->username) }}" autocomplete="off">
                    @error('username')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.device.password') }}</span>
                    <input type="password" name="password" autocomplete="new-password" placeholder="{{ $device->exists ? '••••••••' : '' }}">
                    <small>{{ __('app.device.password_hint') }}</small>
                    @error('password')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            <h3 class="form-section-title">{{ __('app.device.section_location') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.company') }}</span>
                    <select name="company_id" id="company_id">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) old('company_id', $device->company_id) === (string) $company->id)>{{ $company->localizedName() }}</option>
                        @endforeach
                    </select>
                    @error('company_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.branch') }}</span>
                    <select name="branch_id" id="branch_id" data-selected="{{ old('branch_id', $device->branch_id) }}">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" data-company="{{ $branch->company_id }}" @selected((string) old('branch_id', $device->branch_id) === (string) $branch->id)>{{ $branch->localizedName() }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.device.location_description') }}</span>
                    <input name="location_description" value="{{ old('location_description', $device->location_description) }}">
                    @error('location_description')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.device.timezone') }}</span>
                    <input name="timezone" dir="ltr" value="{{ old('timezone', $device->timezone ?? 'Asia/Riyadh') }}">
                    @error('timezone')<small>{{ $message }}</small>@enderror
                </label>
                <label class="form-span-2">
                    <span>{{ __('app.device.notes') }}</span>
                    <input name="notes" value="{{ old('notes', $device->notes) }}">
                    @error('notes')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit">{{ __('app.save') }}</button>
                <a class="ghost-button" href="{{ route('devices.index') }}">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </section>

    <script>
        (function () {
            const conn = document.getElementById('connection_type');
            const company = document.getElementById('company_id');
            const branch = document.getElementById('branch_id');

            function toggleConn() {
                const isDdns = conn.value === 'ddns';
                document.querySelectorAll('[data-conn="ip"]').forEach(el => el.hidden = isDdns);
                document.querySelectorAll('[data-conn="domain"]').forEach(el => el.hidden = !isDdns);
            }

            function filterBranches() {
                const selected = company.value;
                Array.from(branch.options).forEach(function (opt) {
                    if (!opt.value) return;
                    const match = opt.getAttribute('data-company') === selected;
                    opt.hidden = !match;
                    if (!match && opt.selected) branch.value = '';
                });
            }

            conn.addEventListener('change', toggleConn);
            company.addEventListener('change', filterBranches);
            toggleConn();
            filterBranches();
        })();
    </script>
@endsection
