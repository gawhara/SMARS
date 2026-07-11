@extends('layouts.app')

@section('title', __('app.branches'))

@section('content')
    @php
        $totalBranchCount = $companies->sum('branches_count');
        $selectedCompany = request('company_id') ? $companies->firstWhere('id', (int) request('company_id')) : null;
    @endphp

    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.company_specific') }}</span>
            <h1>{{ __('app.branches') }}</h1>
            <p>{{ __('app.organization_note') }}</p>
        </div>
        <a class="primary-button" href="{{ route('branches.create') }}">{{ __('app.add_new') }}</a>
    </section>

    @include('partials.flash')

    <section class="branch-metrics">
        <article class="mini-stat">
            <span class="mini-stat-label">{{ __('app.companies') }}</span>
            <strong class="mini-stat-value">{{ number_format($companies->count()) }}</strong>
        </article>
        <article class="mini-stat">
            <span class="mini-stat-label">{{ __('app.branches') }}</span>
            <strong class="mini-stat-value tone-info">{{ number_format($totalBranchCount) }}</strong>
        </article>
        <article class="mini-stat">
            <span class="mini-stat-label">{{ __('app.filters') }}</span>
            <strong class="mini-stat-value">{{ number_format($branches->total()) }}</strong>
        </article>
        <article class="mini-stat accent">
            <span class="mini-stat-label">{{ __('app.company') }}</span>
            <strong class="mini-stat-value">{{ $selectedCompany ? $selectedCompany->code : __('app.all_companies') }}</strong>
        </article>
    </section>

    <section class="panel branch-company-panel">
        <div class="panel-header">
            <div>
                <h2>{{ __('app.all_companies') }}</h2>
                <p>{{ __('app.company_specific') }}</p>
            </div>
        </div>
        @include('partials.company-picker', [
            'companies' => $companies,
            'route' => 'branches.index',
            'countField' => 'branches_count',
            'label' => __('app.branches'),
        ])
    </section>

    <div class="resource-workspace">
        <section class="panel resource-main">
            <div class="panel-header">
                <div>
                    <h2>{{ __('app.branches') }}</h2>
                    <p>{{ $branches->total() }} {{ __('app.branches') }}</p>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.company') }}</th>
                            <th>{{ __('app.name_ar') }}</th>
                            <th>{{ __('app.name_en') }}</th>
                            <th>{{ __('app.location') }}</th>
                            <th>{{ __('app.status') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $branch)
                            <tr>
                                <td>{{ $branch->company?->localizedName() }}</td>
                                <td>{{ $branch->name_ar }}</td>
                                <td>{{ $branch->name_en }}</td>
                                <td>{{ $branch->location }}</td>
                                <td><span class="status-badge {{ $branch->is_active ? 'success' : 'muted' }}">{{ $branch->is_active ? __('app.active') : __('app.inactive') }}</span></td>
                                <td class="table-actions">
                                    <a class="ghost-button" href="{{ route('branches.edit', $branch) }}">{{ __('app.edit') }}</a>
                                    <form method="POST" action="{{ route('branches.destroy', $branch) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger-button" type="submit">{{ __('app.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty-row">{{ __('app.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $branches->links() }}
        </section>

        <aside class="panel resource-filter-panel">
            <div class="panel-header">
                <div>
                    <h2>{{ __('app.filters') }}</h2>
                    <p>{{ __('app.company_specific') }}</p>
                </div>
            </div>
            <form class="filter-stack" method="GET">
                <label>
                    <span>{{ __('app.search') }}</span>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search') }}">
                </label>
                <label>
                    <span>{{ __('app.company') }}</span>
                    <select name="company_id">
                        <option value="">{{ __('app.all_companies') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>{{ __('app.status') }}</span>
                    <select name="status">
                        <option value="">{{ __('app.all_statuses') }}</option>
                        <option value="1" @selected(request('status') === '1')>{{ __('app.active') }}</option>
                        <option value="0" @selected(request('status') === '0')>{{ __('app.inactive') }}</option>
                    </select>
                </label>
                <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
            </form>
        </aside>
    </div>
@endsection
