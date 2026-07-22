@extends('layouts.app')

@section('title', __('app.provision.title'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ $device->device_name }}</span>
            <h1>{{ __('app.provision.title') }}</h1>
        </div>
        <a class="ghost-button" href="{{ route('devices.enrollments.index', $device) }}">{{ __('app.provision.back_to_enroll') }}</a>
    </section>

    @include('partials.flash')

    <div class="callout callout-warning">
        <strong>{{ __('app.provision.warning_title') }}</strong>
        <p>{{ __('app.provision.warning_body') }}</p>
    </div>

    @if ($candidates->isEmpty())
        <section class="panel"><div class="panel-body"><p class="muted-note">{{ __('app.provision.no_candidates') }}</p></div></section>
    @else
        <form method="POST" data-provision-form>
            @csrf

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2>{{ __('app.provision.select_employees') }}</h2>
                        <p>{{ __('app.provision.select_hint') }}</p>
                    </div>
                    <label class="enroll-option" style="margin:0">
                        <input type="checkbox" data-select-all>
                        <span>{{ __('app.provision.select_all') }}</span>
                    </label>
                </div>
                <div class="panel-body">
                    <div class="enroll-list">
                        @foreach ($candidates as $employee)
                            <label class="enroll-option">
                                <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" data-employee
                                    @disabled(blank($employee->hr_employee_id))>
                                <span>
                                    {{ $employee->localizedName() }}
                                    <small>· {{ $employee->hr_employee_id ?: __('app.provision.no_hr_id') }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="muted-note">{{ __('app.provision.hr_id_note') }}</p>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header"><h2>{{ __('app.provision.action_title') }}</h2></div>
                <div class="panel-body">
                    <div class="copy-form" style="align-items:flex-end">
                        <label>
                            <span>{{ __('app.provision.target') }}</span>
                            <select name="target">
                                @if ($otherDevices->isNotEmpty())
                                    <option value="all">★ {{ __('app.enroll.all_devices') }} ({{ $otherDevices->count() }})</option>
                                    @foreach ($otherDevices as $other)
                                        <option value="{{ $other->id }}">{{ $other->device_name }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled>{{ __('app.enroll.no_target') }}</option>
                                @endif
                            </select>
                        </label>
                    </div>

                    <div class="table-actions" style="margin-top:16px;flex-wrap:wrap;gap:10px">
                        <button class="primary-button" type="submit"
                            formaction="{{ route('devices.provision.copy', $device) }}"
                            @disabled($otherDevices->isEmpty())
                            data-needs-selection>{{ __('app.provision.copy') }}</button>

                        <button class="ghost-button" type="submit"
                            formaction="{{ route('devices.provision.move', $device) }}"
                            @disabled($otherDevices->isEmpty())
                            onclick="return confirm('{{ __('app.provision.confirm_move') }}')"
                            data-needs-selection>{{ __('app.provision.move') }}</button>

                        <button class="danger-button" type="submit"
                            formaction="{{ route('devices.provision.delete', $device) }}"
                            onclick="return confirm('{{ __('app.provision.confirm_delete') }}')"
                            data-needs-selection>{{ __('app.provision.delete') }}</button>
                    </div>
                    <p class="muted-note">
                        {{ __('app.provision.copy_hint') }} · {{ __('app.provision.move_hint') }} · {{ __('app.provision.delete_hint') }}
                    </p>
                </div>
            </section>
        </form>
    @endif
@endsection
