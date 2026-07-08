@extends('layouts.app')

@section('title', __('app.shifts'))

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.global_record') }}</span>
            <h1>{{ __('app.shifts') }}</h1>
            <p>{{ __('app.organization_note') }}</p>
        </div>
        <a class="primary-button" href="{{ route('shifts.create') }}">{{ __('app.add_new') }}</a>
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
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.name_ar') }}</th>
                        <th>{{ __('app.name_en') }}</th>
                        <th>{{ __('app.start_time') }}</th>
                        <th>{{ __('app.end_time') }}</th>
                        <th>{{ __('app.status') }}</th>
                        <th>{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shifts as $shift)
                        <tr>
                            <td>{{ $shift->name_ar }}</td>
                            <td>{{ $shift->name_en }}</td>
                            <td>{{ \Illuminate\Support\Str::substr($shift->start_time, 0, 5) }}</td>
                            <td>{{ \Illuminate\Support\Str::substr($shift->end_time, 0, 5) }}</td>
                            <td><span class="status-badge {{ $shift->is_active ? 'success' : 'muted' }}">{{ $shift->is_active ? __('app.active') : __('app.inactive') }}</span></td>
                            <td class="table-actions">
                                <a class="ghost-button" href="{{ route('shifts.edit', $shift) }}">{{ __('app.edit') }}</a>
                                <form method="POST" action="{{ route('shifts.destroy', $shift) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
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
        {{ $shifts->links() }}
    </section>
@endsection
