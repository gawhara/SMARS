@extends('layouts.app')

@section('title', __('app.att.import_title'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.att.import_title') }}</h1>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="panel form-panel">
        <p class="muted-note" style="margin-top:0">{{ __('app.att.import_intro') }}</p>

        <form method="POST" action="{{ route('attendance.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-grid">
                <label>
                    <span>{{ __('app.att.import_file') }} *</span>
                    <input type="file" name="file" accept=".csv,.txt" required>
                    @error('file')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.att.import_device') }}</span>
                    <select name="attendance_machine_id">
                        <option value="">{{ __('app.select_placeholder') }}</option>
                        @foreach ($machines as $machine)
                            <option value="{{ $machine->id }}" @selected((string) old('attendance_machine_id') === (string) $machine->id)>{{ $machine->device_name }}</option>
                        @endforeach
                    </select>
                    @error('attendance_machine_id')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            <div class="import-format">
                <code>{{ __('app.att.import_format') }}</code>
                <pre>device_user_id,punch_at,punch_type,verification_type
EMP-0001,2026-07-11 08:03:00,in,fingerprint
EMP-0001,2026-07-11 17:10:00,out,fingerprint</pre>
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit">{{ __('app.att.import_run') }}</button>
                <a class="ghost-button" href="{{ route('attendance.index') }}">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
