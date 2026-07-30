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

    <section class="panel filter-panel calc-filter">
        <form method="GET">
            <div class="calc-filter-row">
                <label class="att-field calc-field-grow">
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
                <button class="primary-button calc-run" type="submit">{{ __('app.latency.run') }}</button>
            </div>
            <div class="att-presets calc-presets">
                <span class="calc-presets-label">{{ __('app.latency.quick_range') }}</span>
                @foreach (['this_month', 'last_month', 'last_3_months', 'this_year'] as $preset)
                    <button type="button" class="att-preset" data-range="{{ $preset }}">{{ __('app.latency.preset_'.$preset) }}</button>
                @endforeach
            </div>
        </form>
    </section>

    @if (! $result)
        <div class="calc-empty">
            <div class="calc-empty-icon">🕒</div>
            <strong>{{ __('app.latency.calculator') }}</strong>
            <p>{{ __('app.latency.pick_prompt') }}</p>
        </div>
    @else
        @php $r = $result; @endphp

        {{-- Money summary hero --}}
        <div class="calc-summary">
            <div class="calc-summary-total">
                <span class="calc-summary-label">{{ __('app.latency.total_deduction') }}</span>
                <strong>{{ number_format($r['total_deduction'], 2) }}</strong>
                <small>{{ __('app.currency') }}</small>
            </div>
            <div class="calc-summary-split">
                <div class="calc-split-item late">
                    <span>{{ __('app.latency.late') }}</span>
                    <strong>{{ number_format($r['late_amount'], 2) }}</strong>
                </div>
                <div class="calc-split-item absence">
                    <span>{{ __('app.latency.absence') }}</span>
                    <strong>{{ number_format($r['absence_amount'], 2) }}</strong>
                </div>
            </div>
            <div class="calc-summary-meta">
                <div class="calc-meta-emp">
                    <strong>{{ $r['employee']->localizedName() }}</strong>
                    <span><bdi dir="ltr">{{ $r['from']->format('d/m/Y') }}</bdi> — <bdi dir="ltr">{{ $r['to']->format('d/m/Y') }}</bdi></span>
                </div>
                <span class="calc-policy-chip">{{ $r['policy']->name }} · <bdi dir="ltr">{{ __('app.latency.rule_line', ['grace' => (int) $r['policy']->grace_minutes, 'round' => $r['policy']->round_up_to_hour ? __('app.latency.round_hours') : __('app.latency.round_exact'), 'mult' => rtrim(rtrim(number_format((float) $r['policy']->multiplier, 2), '0'), '.')]) }}</bdi></span>
                <div class="calc-rates">
                    <span>{{ __('app.latency.salary_basis') }} <b>{{ number_format($r['salary_basis'], 2) }}</b></span>
                    <span>{{ __('app.latency.hourly_rate') }} <b>{{ number_format($r['hourly_rate'], 2) }}</b></span>
                    <span>{{ __('app.latency.daily_rate') }} <b>{{ number_format($r['daily_rate'], 2) }}</b></span>
                </div>
            </div>
        </div>

        {{-- Count metrics --}}
        <div class="att-metrics calc-metrics">
            <div class="att-metric" style="--m:#f59e0b"><span class="att-metric-label">{{ __('app.latency.late_days') }}</span><span class="att-metric-value">{{ number_format($r['late_days']) }}</span></div>
            <div class="att-metric" style="--m:#f97316"><span class="att-metric-label">{{ __('app.latency.late_minutes_total') }}</span><span class="att-metric-value">{{ number_format($r['late_minutes_total']) }}</span></div>
            <div class="att-metric" style="--m:#8b5cf6"><span class="att-metric-label">{{ __('app.latency.late_hours') }}</span><span class="att-metric-value">{{ number_format($r['late_hours'], 1) }}</span></div>
            <div class="att-metric" style="--m:#ef4444"><span class="att-metric-label">{{ __('app.latency.absent_days') }}</span><span class="att-metric-value">{{ number_format($r['absent_days']) }}</span></div>
            <div class="att-metric" style="--m:#e11d48"><span class="att-metric-label">{{ __('app.latency.penalty_days') }}</span><span class="att-metric-value">{{ number_format($r['penalty_days']) }}</span></div>
        </div>

        {{-- Day-by-day breakdown --}}
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
                                <td>{{ $day['late_minutes'] ? number_format($day['late_minutes']) : '—' }}</td>
                                <td>{{ $day['late_hours'] ? number_format($day['late_hours'], 2) : '—' }}</td>
                                <td class="money-value">{{ $day['late_amount'] ? number_format($day['late_amount'], 2) : '—' }}</td>
                                <td>{{ $day['penalty_days'] ? number_format($day['penalty_days']) : '—' }}</td>
                                <td class="money-value">{{ $day['absence_amount'] ? number_format($day['absence_amount'], 2) : '—' }}</td>
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
