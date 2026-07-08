@extends('layouts.app')

@section('title', $department->exists ? __('app.edit') : __('app.add_new'))

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.global_record') }}</span>
            <h1>{{ $department->exists ? __('app.edit') : __('app.add_new') }} - {{ __('app.departments') }}</h1>
            <p>{{ __('app.organization_note') }}</p>
        </div>
    </section>

    <section class="panel form-panel">
        <form method="POST" action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}">
            @csrf
            @if ($department->exists)
                @method('PUT')
            @endif

            <div class="form-grid">
                <label>
                    <span>{{ __('app.name_ar') }}</span>
                    <input name="name_ar" value="{{ old('name_ar', $department->name_ar) }}" required>
                    @error('name_ar')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.name_en') }}</span>
                    <input name="name_en" value="{{ old('name_en', $department->name_en) }}" required>
                    @error('name_en')<small>{{ $message }}</small>@enderror
                </label>
                <label class="check-row">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $department->is_active ?? true))>
                    <span>{{ __('app.active') }}</span>
                </label>
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit">{{ __('app.save') }}</button>
                <a class="ghost-button" href="{{ route('departments.index') }}">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
