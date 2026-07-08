@extends('layouts.app')

@section('title', $branch->exists ? __('app.edit') : __('app.add_new'))

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.company_specific') }}</span>
            <h1>{{ $branch->exists ? __('app.edit') : __('app.add_new') }} - {{ __('app.branches') }}</h1>
            <p>{{ __('app.organization_note') }}</p>
        </div>
    </section>

    <section class="panel form-panel">
        <form method="POST" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}">
            @csrf
            @if ($branch->exists)
                @method('PUT')
            @endif

            <div class="form-grid">
                <label>
                    <span>{{ __('app.company') }}</span>
                    <select name="company_id" required>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) old('company_id', $branch->company_id) === (string) $company->id)>{{ $company->localizedName() }}</option>
                        @endforeach
                    </select>
                    @error('company_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.name_ar') }}</span>
                    <input name="name_ar" value="{{ old('name_ar', $branch->name_ar) }}" required>
                    @error('name_ar')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.name_en') }}</span>
                    <input name="name_en" value="{{ old('name_en', $branch->name_en) }}" required>
                    @error('name_en')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.location') }}</span>
                    <input name="location" value="{{ old('location', $branch->location) }}">
                    @error('location')<small>{{ $message }}</small>@enderror
                </label>
                <label class="check-row">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $branch->is_active ?? true))>
                    <span>{{ __('app.active') }}</span>
                </label>
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit">{{ __('app.save') }}</button>
                <a class="ghost-button" href="{{ route('branches.index') }}">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
