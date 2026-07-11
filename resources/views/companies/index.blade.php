@extends('layouts.app')

@section('title', __('app.companies'))

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.organization_structure') }}</span>
            <h1>{{ __('app.companies') }}</h1>
            <p>{{ __('app.organization_note') }}</p>
        </div>
        <a class="primary-button" href="{{ route('companies.create') }}">{{ __('app.add_new') }}</a>
    </section>

    @include('partials.flash')

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search') }}">
            <select name="status">
                <option value="">{{ __('app.all_statuses') }}</option>
                <option value="1" @selected(request('status') === '1')>{{ __('app.active') }}</option>
                <option value="0" @selected(request('status') === '0')>{{ __('app.inactive') }}</option>
            </select>
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    <div class="list-toolbar">
        <div class="list-count">
            <strong>{{ $companies->total() }}</strong>
            <span>{{ __('app.companies') }}</span>
        </div>
    </div>

    <div class="company-grid">
        @forelse ($companies as $company)
            <article class="company-card">
                <div class="company-card-head">
                    @include('partials.company-mark', ['company' => $company])
                    <span class="status-badge {{ $company->is_active ? 'success' : 'muted' }}">
                        {{ $company->is_active ? __('app.active') : __('app.inactive') }}
                    </span>
                </div>

                <h3 class="company-card-title">
                    <a href="{{ route('companies.show', $company) }}" class="stretched-link">{{ $company->localizedName() }}</a>
                </h3>
                <p class="company-card-sub">{{ $company->name_en }} / {{ $company->code }}</p>

                <dl class="company-card-meta">
                    <div>
                        <dt>{{ __('app.branches_count') }}</dt>
                        <dd>{{ $company->branches_count }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('app.company_info.cr_number') }}</dt>
                        <dd>{{ $company->cr_number ?: __('app.none') }}</dd>
                    </div>
                </dl>

                <div class="company-card-actions">
                    <a class="ghost-button" href="{{ route('companies.show', $company) }}">{{ __('app.view') }}</a>
                    <a class="ghost-button" href="{{ route('companies.edit', $company) }}">{{ __('app.edit') }}</a>
                    <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="danger-button" type="submit">{{ __('app.delete') }}</button>
                    </form>
                </div>
            </article>
        @empty
            <section class="panel empty-state company-empty">
                <div class="empty-icon">@include('partials.icon', ['name' => 'building', 'class' => 'empty-icon-svg'])</div>
                <h3>{{ __('app.none') }}</h3>
                <a class="primary-button" href="{{ route('companies.create') }}">{{ __('app.add_new') }}</a>
            </section>
        @endforelse
    </div>

    <div class="company-grid-pagination">
        {{ $companies->links() }}
    </div>
@endsection
