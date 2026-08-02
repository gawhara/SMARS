@extends('layouts.app')

@section('title', __('app.ot.calculator'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.payroll') }}</span>
            <h1>{{ __('app.ot.calculator') }}</h1>
            <p>{{ __('app.ot.subtitle') }}</p>
        </div>
    </section>

    @include('partials.flash')

    <section class="panel filter-panel calc-filter">
        <div class="calc-filter-head">
            <span class="calc-filter-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div>
                <strong>{{ __('app.ot.filter_title') }}</strong>
                <small>{{ __('app.ot.filter_hint') }}</small>
            </div>
        </div>
        <form method="GET">
            <div class="calc-filter-row">
                <label class="att-field calc-field-grow">
                    <span>{{ __('app.latency.select_employee') }}</span>
                    <span class="field-control">
                        <svg class="fc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
                        <select name="employee_id" required>
                            <option value="">{{ __('app.select_placeholder') }}</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" @selected($selectedEmployee && $selectedEmployee->id === $emp->id)>
                                    {{ $emp->localizedName() }} · {{ $emp->employee_code }}
                                </option>
                            @endforeach
                        </select>
                    </span>
                </label>
                <div class="calc-daterange">
                    <label class="att-field">
                        <span>{{ __('app.att.date_from') }}</span>
                        <span class="field-control">
                            <svg class="fc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg>
                            <input type="date" name="date_from" value="{{ $from }}">
                        </span>
                    </label>
                    <span class="calc-daterange-sep" aria-hidden="true">←</span>
                    <label class="att-field">
                        <span>{{ __('app.att.date_to') }}</span>
                        <span class="field-control">
                            <svg class="fc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg>
                            <input type="date" name="date_to" value="{{ $to }}">
                        </span>
                    </label>
                </div>
                <button class="primary-button calc-run" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    {{ __('app.ot.calculate') }}
                </button>
            </div>

            {{-- Manual adjustment: pre-filled from records, editable before paying --}}
            <div class="ot-adjust">
                <span class="ot-adjust-label">{{ __('app.ot.adjust') }}</span>
                <label class="att-field">
                    <span>{{ __('app.ot.hours') }}</span>
                    <span class="field-control">
                        <svg class="fc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <input type="number" name="hours" step="0.25" min="0" value="{{ $result ? $result['hours'] : old('hours') }}" placeholder="{{ __('app.ot.hours_placeholder') }}">
                    </span>
                </label>
                <label class="att-field">
                    <span>{{ __('app.ot.multiplier') }}</span>
                    <span class="field-control">
                        <svg class="fc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
                        <input type="number" name="multiplier" step="0.05" min="1" max="5" value="{{ request('multiplier', $defaultMultiplier) }}">
                    </span>
                </label>
                @if ($result && $result['adjusted'])
                    <a class="ghost-button ot-reset" href="{{ route('overtime.calculator', ['employee_id' => $selectedEmployee->id, 'date_from' => $from, 'date_to' => $to]) }}">{{ __('app.ot.reset') }}</a>
                @endif
            </div>
        </form>
    </section>

    @if (! $result)
        <div class="calc-empty">
            <div class="calc-empty-icon">⏱️</div>
            <strong>{{ __('app.ot.calculator') }}</strong>
            <p>{{ __('app.ot.pick_prompt') }}</p>
        </div>
    @else
        @php $o = $result; @endphp
        <div class="calc-summary">
            <div class="calc-summary-total">
                <span class="calc-summary-label">{{ __('app.ot.pay') }}</span>
                <strong class="tnum">{{ number_format($o['pay'], 2) }}</strong>
                <small>{{ __('app.currency') }}</small>
            </div>
            <div class="calc-summary-split">
                <div class="calc-split-item late">
                    <span>{{ __('app.ot.hours') }}</span>
                    <strong class="tnum">{{ number_format($o['hours'], 2) }}</strong>
                </div>
                <div class="calc-split-item absence">
                    <span>{{ __('app.ot.ot_hour_rate') }}</span>
                    <strong class="tnum">{{ number_format($o['overtime_hour_rate'], 2) }}</strong>
                </div>
            </div>
            <div class="calc-summary-meta">
                <div class="calc-meta-emp">
                    <strong>{{ $o['employee']->localizedName() }}</strong>
                    <span><bdi dir="ltr">{{ $o['from']->format('d/m/Y') }}</bdi> — <bdi dir="ltr">{{ $o['to']->format('d/m/Y') }}</bdi></span>
                </div>
                <span class="calc-policy-chip">
                    {{ __('app.ot.formula', ['mult' => rtrim(rtrim(number_format($o['multiplier'], 2), '0'), '.')]) }}
                    @if ($o['adjusted']) · <b>{{ __('app.ot.adjusted') }}</b> @endif
                </span>
                <div class="calc-rates">
                    <span>{{ __('app.latency.salary_basis') }} <b class="tnum">{{ number_format($o['salary_basis'], 2) }}</b></span>
                    <span>{{ __('app.latency.hourly_rate') }} <b class="tnum">{{ number_format($o['hourly_rate'], 2) }}</b></span>
                    <span>{{ __('app.ot.from_records') }} <b class="tnum">{{ number_format($o['baseline_hours'], 2) }}</b></span>
                </div>
            </div>
        </div>

        <div class="att-metrics calc-metrics">
            <div class="att-metric" style="--m:#14b8a6"><span class="calc-metric-ico">📅</span><span class="att-metric-label">{{ __('app.ot.overtime_days') }}</span><span class="att-metric-value">{{ number_format($o['overtime_days']) }}</span></div>
            <div class="att-metric" style="--m:#0ea5e9"><span class="calc-metric-ico">🕐</span><span class="att-metric-label">{{ __('app.ot.from_records') }}</span><span class="att-metric-value">{{ number_format($o['baseline_hours'], 1) }}</span></div>
            <div class="att-metric" style="--m:#8b5cf6"><span class="calc-metric-ico">✏️</span><span class="att-metric-label">{{ __('app.ot.paid_hours') }}</span><span class="att-metric-value">{{ number_format($o['hours'], 1) }}</span></div>
            <div class="att-metric" style="--m:#f59e0b"><span class="calc-metric-ico">×</span><span class="att-metric-label">{{ __('app.ot.multiplier') }}</span><span class="att-metric-value">{{ rtrim(rtrim(number_format($o['multiplier'], 2), '0'), '.') }}</span></div>
            <div class="att-metric" style="--m:#10b981"><span class="calc-metric-ico">💰</span><span class="att-metric-label">{{ __('app.ot.ot_hour_rate') }}</span><span class="att-metric-value">{{ number_format($o['overtime_hour_rate'], 2) }}</span></div>
        </div>

        @if (! empty($o['days']))
            <section class="panel">
                <div class="panel-header"><div><h2>{{ __('app.ot.breakdown') }}</h2><p>{{ count($o['days']) }}</p></div></div>
                <div class="table-wrap">
                    <table class="calc-breakdown">
                        <thead>
                            <tr>
                                <th>{{ __('app.latency.day') }}</th>
                                <th>{{ __('app.ot.hours') }}</th>
                                <th>{{ __('app.ot.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($o['days'] as $day)
                                <tr>
                                    <td><strong><bdi dir="ltr">{{ $day['date']->format('d/m/Y') }}</bdi></strong></td>
                                    <td class="tnum">{{ number_format($day['overtime_hours'], 2) }}</td>
                                    <td class="money-value money-late">{{ number_format($day['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($o['adjusted'])
                    <p class="note">{{ __('app.ot.adjusted_note', ['records' => number_format($o['baseline_hours'], 2), 'paid' => number_format($o['hours'], 2)]) }}</p>
                @endif
            </section>
        @endif
    @endif
@endsection
