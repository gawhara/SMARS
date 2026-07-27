@extends('layouts.app')

@section('title', __('app.deduct.title'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.payroll') }}</span>
            <h1>{{ __('app.deduct.title') }}</h1>
            <p>{{ __('app.deduct.intro') }}</p>
        </div>
    </section>

    @include('partials.flash')

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <select name="company_id">
                @foreach ($companies as $c)
                    <option value="{{ $c->id }}" @selected($company && $company->id === $c->id)>{{ $c->localizedName() }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" aria-label="{{ __('app.att.date_from') }}">
            <input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" aria-label="{{ __('app.att.date_to') }}">
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
        <p class="muted-note" style="margin:12px 0 0">{{ __('app.att.period_range', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}</p>
    </section>

    <div class="mini-stats">
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.deduct.employees') }}</span>
            <strong class="mini-stat-value">{{ number_format($totals['employees']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.deduct.total_deduction') }}</span>
            <strong class="mini-stat-value {{ $totals['deduction'] > 0 ? 'text-danger' : '' }}">{{ number_format($totals['deduction'], 2) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.deduct.to_review') }}</span>
            <strong class="mini-stat-value {{ $totals['reviews'] > 0 ? 'tone-warning' : '' }}">{{ number_format($totals['reviews']) }}</strong>
        </div>
    </div>

    @if ($rows->count())
        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.att.employee') }}</th>
                            <th>{{ __('app.deduct.salary_basis') }}</th>
                            <th>{{ __('app.deduct.late_hours') }}</th>
                            <th>{{ __('app.deduct.early_hours') }}</th>
                            <th>{{ __('app.deduct.missing_hours') }}</th>
                            <th>{{ __('app.deduct.absence_days') }}</th>
                            <th>{{ __('app.deduct.total_deduction') }}</th>
                            <th>{{ __('app.deduct.net_salary') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    <a class="cell-name" href="{{ route('payroll.deductions.employee', [$row['employee'], 'date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]) }}">{{ $row['employee']->localizedName() }}</a>
                                    <small>{{ $row['employee']->employee_code }}
                                        @if ($row['review_count'] > 0)· <span class="tone-warning">{{ $row['review_count'] }} {{ __('app.deduct.needs_review') }}</span>@endif
                                    </small>
                                </td>
                                <td dir="ltr">{{ number_format($row['salary_basis'], 2) }}</td>
                                <td>{{ $row['late_hours'] }}</td>
                                <td>{{ $row['early_hours'] }}</td>
                                <td>{{ $row['missing_hours'] }}</td>
                                <td class="{{ $row['penalty_days'] > 0 ? 'text-danger' : '' }}">{{ $row['penalty_days'] }}</td>
                                <td dir="ltr"><strong class="{{ $row['total_deduction'] > 0 ? 'text-danger' : '' }}">{{ number_format($row['total_deduction'], 2) }}</strong></td>
                                <td dir="ltr"><strong>{{ number_format($row['net_salary'], 2) }}</strong></td>
                                <td class="table-actions">
                                    <a class="ghost-button" href="{{ route('payroll.deductions.employee', [$row['employee'], 'date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]) }}">{{ __('app.deduct.view_detail') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="panel empty-state">
            <span class="empty-icon">@include('partials.icon', ['name' => 'wallet', 'class' => 'empty-icon-svg'])</span>
            <h3>{{ __('app.deduct.no_deductions') }}</h3>
        </section>
    @endif
@endsection
