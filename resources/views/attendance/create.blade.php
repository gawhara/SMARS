@extends('layouts.app')

@section('title', __('app.att.add_manual'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.att.add_manual') }}</h1>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="panel form-panel">
        <form method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <div class="form-grid">
                <label>
                    <span>{{ __('app.att.employee') }} *</span>
                    <select name="employee_id" required>
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) old('employee_id', $record->employee_id) === (string) $employee->id)>{{ $employee->localizedName() }} · {{ $employee->employee_code }}</option>
                        @endforeach
                    </select>
                    @error('employee_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.att.machine') }}</span>
                    <select name="attendance_machine_id">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($machines as $machine)
                            <option value="{{ $machine->id }}" @selected((string) old('attendance_machine_id', $record->attendance_machine_id) === (string) $machine->id)>{{ $machine->device_name }}</option>
                        @endforeach
                    </select>
                    @error('attendance_machine_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.att.punch_at') }} *</span>
                    <input type="datetime-local" name="punch_at" value="{{ old('punch_at', $record->punch_at) }}" required>
                    @error('punch_at')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.att.punch_type') }} *</span>
                    <select name="punch_type" required>
                        @foreach (['in', 'out', 'unknown'] as $type)
                            <option value="{{ $type }}" @selected(old('punch_type', $record->punch_type) === $type)>{{ __('app.att.punch_'.$type) }}</option>
                        @endforeach
                    </select>
                    @error('punch_type')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.att.verification') }}</span>
                    <input name="verification_type" value="{{ old('verification_type', $record->verification_type) }}" placeholder="fingerprint">
                    @error('verification_type')<small>{{ $message }}</small>@enderror
                </label>
                <label class="form-span-2">
                    <span>{{ __('app.device.notes') }}</span>
                    <input name="notes" value="{{ old('notes', $record->notes) }}">
                    @error('notes')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit">{{ __('app.save') }}</button>
                <a class="ghost-button" href="{{ route('attendance.index') }}">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
