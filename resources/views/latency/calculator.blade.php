@extends('layouts.app')

@section('title', __('app.latency.calculator'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.payroll') }}</span>
            <h1>{{ __('app.latency.calculator') }}</h1>
            <p>{{ __('app.latency.calculator_subtitle') }}</p>
        </div>
        <div class="table-actions">
            <a class="ghost-button" href="{{ route('latency.policies.index') }}">{{ __('app.latency.policies') }}</a>
        </div>
    </section>

    @include('partials.flash')

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <label class="att-field">
                <span>{{ __('app.latency.select_employee') }}</span>
                <select name="employee_id" required>
                    <option value="">{{ __('app.select_placeholder') }}</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}" @selected($selectedEmployee && $selectedEmployee->id === $emp->id)>
                            {{ $emp->localizedName() }} · {{ $emp->employee_code }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="att-field">
                <span>{{ __('app.att.date_from') }}</span>
                <input type="date" name="date_from" value="{{ $from }}">
            </label>
            <label class="att-field">
                <span>{{ __('app.att.date_to') }}</span>
                <input type="date" name="date_to" value="{{ $to }}">
            </label>
            <label class="att-field">
                <span>{{ __('app.latency.override_policy') }}</span>
                <select name="policy_id">
                    <option value="">{{ __('app.latency.employee_default_policy') }}</option>
                    @foreach ($policies as $p)
                        <option value="{{ $p->id }}" @selected(request('policy_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="primary-button" type="submit">{{ __('app.latency.run') }}</button>
        </form>
    </section>

    @if (! $result)
        <div class="profile-empty-state"><strong>{{ __('app.latency.calculator') }}</strong><p>{{ __('app.latency.pick_prompt') }}</p></div>
    @else
        @php $r = $result; @endphp
        <div class="att-rate-card" style="margin-bottom:16px">
            <div class="att-rate-meta">
                <span class="att-rate-label">{{ __('app.latency.applied_policy') }}</span>
                <h2>{{ $r['policy']->name }}
                    <span class="status-badge info">{{ __('app.latency.rule_line', ['grace' => (int) $r['policy']->grace_minutes, 'round' => $r['policy']->round_up_to_hour ? __('app.latency.round_hours') : __('app.latency.round_exact'), 'mult' => rtrim(rtrim(number_format((float) $r['policy']->multiplier, 2), '0'), '.')]) }}</span>
                </h2>
                <p>{{ $r['employee']->localizedName() }} · <bdi dir="ltr">{{ $r['from']->format('d/m/Y') }}</bdi> — <bdi dir="ltr">{{ $r['to']->format('d/m/Y') }}</bdi>
                    · {{ __('app.latency.salary_basis') }} {{ number_format($r['salary_basis'], 2) }}
                    · {{ __('app.latency.hourly_rate') }} {{ number_format($r['hourly_rate'], 2) }}
                    · {{ __('app.latency.daily_rate') }} {{ number_format($r['daily_rate'], 2) }}</p>
            </div>
        </div>

        <div class="att-metrics">
            <div class="att-metric" style="--m:#f59e0b"><span class="att-metric-label">{{ __('app.latency.late_days') }}</span><span class="att-metric-value">{{ number_format($r['late_days']) }}</span></div>
            <div class="att-metric" style="--m:#f59e0b"><span class="att-metric-label">{{ __('app.latency.late_minutes_total') }}</span><span class="att-metric-value">{{ number_format($r['late_minutes_total']) }}</span></div>
            <div class="att-metric" style="--m:#f97316"><span class="att-metric-label">{{ __('app.latency.late_amount') }}</span><span class="att-metric-value">{{ number_format($r['late_amount'], 2) }}</span></div>
            <div class="att-metric" style="--m:#ef4444"><span class="att-metric-label">{{ __('app.latency.absent_days') }}</span><span class="att-metric-value">{{ number_format($r['absent_days']) }}</span></div>
            <div class="att-metric" style="--m:#ef4444"><span class="att-metric-label">{{ __('app.latency.absence_amount') }}</span><span class="att-metric-value">{{ number_format($r['absence_amount'], 2) }}</span></div>
            <div class="att-metric" style="--m:#4f46e5"><span class="att-metric-label">{{ __('app.latency.total_deduction') }}</span><span class="att-metric-value">{{ number_format($r['total_deduction'], 2) }}</span></div>
        </div>

        <section class="panel">
            <div class="panel-header"><div><h2>{{ __('app.latency.breakdown') }}</h2><p>{{ count($r['days']) }}</p></div></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.latency.day') }}</th>
                            <th>{{ __('app.latency.late_minutes') }}</th>
                            <th>{{ __('app.latency.late_hours') }}</th>
                            <th>{{ __('app.latency.late_amount') }}</th>
                            <th>{{ __('app.latency.penalty_days') }}</th>
                            <th>{{ __('app.latency.absence_amount') }}</th>
                            <th>{{ __('app.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($r['days'] as $day)
                            <tr>
                                <td><strong><bdi dir="ltr">{{ $day['date']->format('d/m/Y') }}</bdi></strong></td>
                                <td>{{ number_format($day['late_minutes']) }}</td>
                                <td>{{ number_format($day['late_hours'], 2) }}</td>
                                <td class="money-value">{{ number_format($day['late_amount'], 2) }}</td>
                                <td>{{ number_format($day['penalty_days']) }}</td>
                                <td class="money-value">{{ number_format($day['absence_amount'], 2) }}</td>
                                <td><span class="status-badge {{ $day['penalty_days'] > 0 ? 'warning' : 'info' }}">{{ __('app.att.summary_'.$day['status']) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty-row">{{ __('app.latency.no_deductions') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
