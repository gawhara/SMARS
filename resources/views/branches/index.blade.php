@extends('layouts.app')

@section('title', __('app.branches'))

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.company_specific') }}</span>
            <h1>{{ __('app.branches') }}</h1>
            <p>{{ __('app.organization_note') }}</p>
        </div>
        <a class="primary-button" href="{{ route('branches.create') }}">{{ __('app.add_new') }}</a>
    </section>

    @include('partials.flash')

    @include('partials.company-picker', [
        'companies' => $companies,
        'route' => 'branches.index',
        'countField' => 'branches_count',
        'label' => __('app.branches'),
    ])

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search') }}">
            <select name="company_id">
                <option value="">{{ __('app.all_companies') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">{{ __('app.all_statuses') }}</option>
                <option value="1" @selected(request('status') === '1')>{{ __('app.active') }}</option>
                <option value="0" @selected(request('status') === '0')>{{ __('app.inactive') }}</option>
            </select>
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    <section class="panel">
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
                    @foreach ($branches as $branch)
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
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $branches->links() }}
    </section>
@endsection
