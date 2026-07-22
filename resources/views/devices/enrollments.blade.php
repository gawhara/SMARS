@extends('layouts.app')

@section('title', __('app.enroll.title'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ $device->device_name }}</span>
            <h1>{{ __('app.enroll.manage') }}</h1>
        </div>
        <div class="table-actions">
            <a class="primary-button" href="{{ route('devices.provision', $device) }}">{{ __('app.provision.title') }}</a>
            <a class="ghost-button" href="{{ route('devices.show', $device) }}">{{ __('app.cancel') }}</a>
        </div>
    </section>

    @include('partials.flash')

    <div class="detail-columns">
        {{-- Copy enrollments — the main action --}}
        <section class="panel">
            <div class="panel-header"><h2>{{ __('app.enroll.copy_title') }}</h2></div>
            <div class="panel-body">
                <p class="muted-note" style="margin-top:0">{{ __('app.enroll.copy_intro') }}</p>
                @if ($otherDevices->isNotEmpty() && $enrollments->isNotEmpty())
                    <form method="POST" action="{{ route('devices.enrollments.copy', $device) }}" class="copy-form">
                        @csrf
                        <label>
                            <span>{{ __('app.enroll.copy_to') }}</span>
                            <select name="target" required>
                                <option value="all">★ {{ __('app.enroll.all_devices') }} ({{ $otherDevices->count() }})</option>
                                @foreach ($otherDevices as $other)
                                    <option value="{{ $other->id }}">{{ $other->device_name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="primary-button" type="submit">{{ __('app.enroll.copy_button') }}</button>
                    </form>
                @else
                    <p class="muted-note">{{ $enrollments->isEmpty() ? __('app.enroll.source_empty') : __('app.enroll.no_target') }}</p>
                @endif
            </div>
        </section>

        {{-- Enroll employees --}}
        <section class="panel">
            <div class="panel-header"><h2>{{ __('app.enroll.add_title') }}</h2></div>
            <div class="panel-body">
                @if ($available->isNotEmpty())
                    <p class="muted-note" style="margin-top:0">{{ __('app.enroll.add_hint') }}</p>
                    <form method="POST" action="{{ route('devices.enrollments.store', $device) }}">
                        @csrf
                        <div class="enroll-list">
                            @foreach ($available as $employee)
                                <label class="enroll-option">
                                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}">
                                    <span>{{ $employee->localizedName() }} <small>· {{ $employee->employee_code }}</small></span>
                                </label>
                            @endforeach
                        </div>
                        <button class="primary-button" type="submit" style="margin-top:14px">{{ __('app.enroll.add_button') }}</button>
                    </form>
                @else
                    <p class="muted-note">{{ __('app.enroll.none_available') }}</p>
                @endif
            </div>
        </section>
    </div>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>{{ __('app.enroll.title') }}</h2>
                <p>{{ $enrollments->count() }} {{ __('app.enroll.count') }}</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.att.employee') }}</th>
                        <th>{{ __('app.enroll.device_user_id') }}</th>
                        <th>{{ __('app.company') }}</th>
                        <th>{{ __('app.enroll.enrolled_at') }}</th>
                        <th>{{ __('app.enroll.copied_from') }}</th>
                        <th>{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td>
                                @if ($enrollment->employee)
                                    <a class="cell-name" href="{{ route('employees.show', $enrollment->employee) }}">{{ $enrollment->employee->localizedName() }}</a>
                                @else
                                    {{ __('app.none') }}
                                @endif
                            </td>
                            <td dir="ltr">{{ $enrollment->device_user_id }}</td>
                            <td>{{ $enrollment->employee?->company?->localizedName() ?? __('app.none') }}</td>
                            <td>{{ optional($enrollment->enrolled_at)->format('Y-m-d') ?? __('app.none') }}</td>
                            <td>{{ $enrollment->source_machine_id ? optional($enrollment->sourceMachine)->device_name : __('app.none') }}</td>
                            <td class="table-actions">
                                <form method="POST" action="{{ route('devices.enrollments.destroy', [$device, $enrollment]) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger-button" type="submit">{{ __('app.enroll.remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">{{ __('app.enroll.no_enrollments') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
