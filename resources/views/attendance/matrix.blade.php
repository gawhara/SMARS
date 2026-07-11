@extends('layouts.app')

@section('title', __('app.att.matrix_title'))

@php
    $cells = [
        'present' => ['label' => '✓', 'class' => 'cell-present'],
        'late' => ['label' => 'L', 'class' => 'cell-late'],
        'absent' => ['label' => '✕', 'class' => 'cell-absent'],
        'rest' => ['label' => '', 'class' => 'cell-rest'],
        'future' => ['label' => '', 'class' => 'cell-future'],
    ];
    $legend = ['present', 'late', 'absent', 'rest', 'holiday', 'leave'];
    $legendClass = ['holiday' => 'cell-holiday', 'leave' => 'cell-leave'];
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.att.matrix_title') }}</h1>
        </div>
        <a class="ghost-button" href="{{ route('attendance.index') }}">{{ __('app.att.back_to_log') }}</a>
    </section>

    @include('partials.flash')

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" aria-label="{{ __('app.att.month') }}">
            <select name="company_id">
                <option value="">{{ __('app.all_companies') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->localizedName() }}</option>
                @endforeach
            </select>
            <select name="branch_id">
                <option value="">{{ __('app.branch') }}</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->localizedName() }}</option>
                @endforeach
            </select>
            <select name="department_id">
                <option value="">{{ __('app.all_departments') }}</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->localizedName() }}</option>
                @endforeach
            </select>
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    <div class="att-legend">
        <span class="legend-title">{{ __('app.att.legend') }}:</span>
        @foreach ($legend as $status)
            <span class="legend-item">
                <span class="legend-swatch {{ $cells[$status]['class'] ?? $legendClass[$status] }}">{{ $cells[$status]['label'] ?? '' }}</span>
                {{ __('app.att.st_'.$status) }}
            </span>
        @endforeach
    </div>

    <section class="panel">
        <div class="table-wrap matrix-wrap">
            <table class="attendance-matrix">
                <thead>
                    <tr>
                        <th class="matrix-name-col">{{ __('app.att.employee') }}</th>
                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            @php $date = $month->copy()->day($day); @endphp
                            <th class="matrix-day {{ $date->isFriday() || $date->isSaturday() ? 'is-weekend' : '' }}">{{ $day }}</th>
                        @endfor
                        <th class="matrix-sum">{{ __('app.att.totals') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php $row = $matrix[$employee->id]; @endphp
                        <tr>
                            <td class="matrix-name-col">
                                <a class="cell-name" href="{{ route('employees.show', $employee) }}">{{ $employee->localizedName() }}</a>
                            </td>
                            @for ($day = 1; $day <= $daysInMonth; $day++)
                                @php $status = $row['days'][$day]; $cell = $cells[$status]; @endphp
                                <td class="matrix-cell {{ $cell['class'] }}" title="{{ __('app.att.st_'.$status) }}">{{ $cell['label'] }}</td>
                            @endfor
                            <td class="matrix-sum">
                                <span class="tone-success">{{ $row['summary']['present'] }}</span> /
                                <span class="tone-warning">{{ $row['summary']['late'] }}</span> /
                                <span class="text-danger">{{ $row['summary']['absent'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $daysInMonth + 2 }}" class="empty-row">{{ __('app.att.no_employees') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
