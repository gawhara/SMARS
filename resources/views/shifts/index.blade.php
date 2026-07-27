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
        <div class="panel-header">
            <div>
                <h2>{{ __('app.shifts') }}</h2>
                <p>{{ $shiftSchedules->count() }} {{ __('app.shifts') }}</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.shift') }}</th>
                        <th>{{ __('app.work_period_1') }}</th>
                        <th>{{ __('app.work_period_2') }}</th>
                        <th>{{ __('app.hours_count') }}</th>
                        <th>{{ __('app.status') }}</th>
                        <th>{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shiftSchedules as $schedule)
                        @php
                            $firstShift = $schedule->firstWhere('shift_number', 1) ?? $schedule->first();
                            $secondShift = $schedule->firstWhere('shift_number', 2);
                            $totalMinutes = $schedule->sum(fn ($shift) => $shift->durationMinutes());
                            $totalHours = $totalMinutes / 60;
                        @endphp
                        <tr>
                            <td><strong>{{ ($firstShift ?? $secondShift)->localizedName() }}</strong></td>
                            <td>
                                @if ($firstShift)
                                    <span class="shift-time-range"><bdi dir="ltr">{{ $firstShift->localizedTime('start_time') }}</bdi><span class="range-sep">–</span><bdi dir="ltr">{{ $firstShift->localizedTime('end_time') }}</bdi></span>
                                @else
                                    {{ __('app.none') }}
                                @endif
                            </td>
                            <td>
                                @if ($secondShift)
                                    <span class="shift-time-range"><bdi dir="ltr">{{ $secondShift->localizedTime('start_time') }}</bdi><span class="range-sep">–</span><bdi dir="ltr">{{ $secondShift->localizedTime('end_time') }}</bdi></span>
                                @else
                                    {{ __('app.none') }}
                                @endif
                            </td>
                            <td>
                                <span class="hours-total">
                                    {{ fmod($totalHours, 1.0) === 0.0 ? number_format($totalHours, 0) : number_format($totalHours, 2) }}
                                    {{ __('app.hours_unit') }}
                                </span>
                            </td>
                            <td>
                                <span class="status-badge {{ $secondShift ? 'info' : 'success' }}">
                                    {{ $secondShift ? __('app.two_shift_schedule') : __('app.continuous_schedule') }}
                                </span>
                            </td>
                            <td class="table-actions">
                                @if ($firstShift)
                                    <a class="ghost-button" href="{{ route('shifts.edit', $firstShift) }}">{{ __('app.edit') }}</a>
                                    <form method="POST" action="{{ route('shifts.destroy', $firstShift) }}" onsubmit="return confirm('{{ __('app.confirm_delete_shift_schedule') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger-button" type="submit">{{ __('app.delete') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">{{ __('app.none') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
