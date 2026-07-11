@extends('layouts.app')

@section('title', __('app.positions'))

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.global_record') }}</span>
            <h1>{{ __('app.positions') }}</h1>
            <p>{{ __('app.organization_note') }}</p>
        </div>
        <a class="primary-button" href="{{ route('positions.create') }}">{{ __('app.add_new') }}</a>
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

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>{{ __('app.positions') }}</h2>
                <p>{{ $positions->total() }} {{ __('app.positions') }}</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.name_ar') }}</th>
                        <th>{{ __('app.name_en') }}</th>
                        <th>{{ __('app.status') }}</th>
                        <th>{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($positions as $position)
                        <tr>
                            <td>{{ $position->name_ar }}</td>
                            <td>{{ $position->name_en }}</td>
                            <td><span class="status-badge {{ $position->is_active ? 'success' : 'muted' }}">{{ $position->is_active ? __('app.active') : __('app.inactive') }}</span></td>
                            <td class="table-actions">
                                <a class="ghost-button" href="{{ route('positions.edit', $position) }}">{{ __('app.edit') }}</a>
                                <form method="POST" action="{{ route('positions.destroy', $position) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger-button" type="submit">{{ __('app.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">{{ __('app.none') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $positions->links() }}
    </section>
@endsection
