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

            <div class="schedule-mode" @if($shift->exists) hidden @endif>
                <span class="schedule-mode-label">{{ __('app.schedule_scenario') }}</span>
                <div class="schedule-options">
                    <label class="schedule-option">
                        <input type="radio" name="schedule_mode" value="single" @checked(old('schedule_mode', 'single') === 'single')>
                        <span>
                            <strong>{{ __('app.one_shift_day') }}</strong>
                            <small>{{ __('app.one_shift_hint') }}</small>
                        </span>
                    </label>
                    <label class="schedule-option">
                        <input type="radio" name="schedule_mode" value="double" @checked(old('schedule_mode') === 'double')>
                        <span>
                            <strong>{{ __('app.two_shifts_day') }}</strong>
                            <small>{{ __('app.two_shifts_hint') }}</small>
                        </span>
                    </label>
                </div>
                @error('schedule_mode')<small class="field-error">{{ $message }}</small>@enderror
            </div>

            @if(app()->isLocale('ar'))
                <div class="form-section">
                    <div class="form-grid">
                        <label class="form-span-2">
                            <span>{{ __('app.schedule_name_ar') }}</span>
                            <input name="schedule_name_ar" value="{{ old('schedule_name_ar', $shift->schedule_name_ar) }}" required placeholder="{{ __('app.schedule_name_ar_placeholder') }}">
                            <small>{{ __('app.schedule_name_ar_hint') }}</small>
                            @error('schedule_name_ar')<small>{{ $message }}</small>@enderror
                        </label>
                    </div>
                </div>
            @endif

            <div class="form-section">
                <div class="form-grid">
                @include('partials.time-12-field', ['name' => 'start_time', 'label' => __('app.start_time'), 'value' => old('start_time', \Illuminate\Support\Str::substr($shift->start_time ?? '', 0, 5)), 'required' => true])
                @include('partials.time-12-field', ['name' => 'end_time', 'label' => __('app.end_time'), 'value' => old('end_time', \Illuminate\Support\Str::substr($shift->end_time ?? '', 0, 5)), 'required' => true])
                </div>
            </div>

            @unless($shift->exists)
                <div class="form-section second-shift-section" data-second-shift @if(old('schedule_mode', 'single') !== 'double') hidden @endif>
                    <div class="form-grid">
                        @include('partials.time-12-field', ['name' => 'second_start_time', 'label' => __('app.start_time'), 'value' => old('second_start_time'), 'required' => false])
                        @include('partials.time-12-field', ['name' => 'second_end_time', 'label' => __('app.end_time'), 'value' => old('second_end_time'), 'required' => false])
                    </div>
                </div>
            @endunless

            <div class="form-section form-section-compact">
                <div class="form-grid">
                <label class="check-row">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shift->is_active ?? true))>
                    <span>{{ __('app.active') }}</span>
                </label>
                </div>
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit">{{ __('app.save') }}</button>
                <a class="ghost-button" href="{{ route('shifts.index') }}">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
