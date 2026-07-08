@extends('layouts.app')

@section('title', $shift->exists ? __('app.edit') : __('app.add_new'))

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.global_record') }}</span>
            <h1>{{ $shift->exists ? __('app.edit') : __('app.add_new') }} - {{ __('app.shifts') }}</h1>
            <p>{{ __('app.organization_note') }}</p>
        </div>
    </section>

    <section class="panel form-panel">
        <form method="POST" action="{{ $shift->exists ? route('shifts.update', $shift) : route('shifts.store') }}">
            @csrf
            @if ($shift->exists)
                @method('PUT')
            @endif

            <div class="form-grid">
                <label>
                    <span>{{ __('app.name_ar') }}</span>
                    <input name="name_ar" value="{{ old('name_ar', $shift->name_ar) }}" required>
                    @error('name_ar')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.name_en') }}</span>
                    <input name="name_en" value="{{ old('name_en', $shift->name_en) }}" required>
                    @error('name_en')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.start_time') }}</span>
                    <input type="time" name="start_time" value="{{ old('start_time', \Illuminate\Support\Str::substr($shift->start_time ?? '', 0, 5)) }}" required>
                    @error('start_time')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.end_time') }}</span>
                    <input type="time" name="end_time" value="{{ old('end_time', \Illuminate\Support\Str::substr($shift->end_time ?? '', 0, 5)) }}" required>
                    @error('end_time')<small>{{ $message }}</small>@enderror
                </label>
                <label class="check-row">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shift->is_active ?? true))>
                    <span>{{ __('app.active') }}</span>
                </label>
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit">{{ __('app.save') }}</button>
                <a class="ghost-button" href="{{ route('shifts.index') }}">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
