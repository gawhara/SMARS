@extends('layouts.app')

@section('title', __('app.eosb.calculator'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.payroll') }}</span>
            <h1>{{ __('app.eosb.calculator') }}</h1>
            <p>{{ __('app.eosb.subtitle') }}</p>
        </div>
    </section>

    @include('partials.flash')

    <section class="panel filter-panel calc-filter">
        <div class="calc-filter-head">
            <span class="calc-filter-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 8l7-5 7 5M5 8v9a2 2 0 002 2h10a2 2 0 002-2V8"/></svg>
            </span>
            <div>
                <strong>{{ __('app.eosb.filter_title') }}</strong>
                <small>{{ __('app.eosb.filter_hint') }}</small>
            </div>
        </div>
        <form method="GET">
            <div class="calc-filter-row">
                <label class="att-field calc-field-grow">
                    <span>{{ __('app.eosb.employee') }}</span>
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
                <label class="att-field">
                    <span>{{ __('app.eosb.end_date') }}</span>
                    <span class="field-control">
                        <svg class="fc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg>
                        <input type="date" name="end_date" value="{{ $endDate }}">
                    </span>
                </label>
                <label class="att-field">
                    <span>{{ __('app.eosb.reason') }}</span>
                    <span class="field-control">
                        <svg class="fc-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <select name="reason">
                            <option value="termination" @selected($reason === 'termination')>{{ __('app.eosb.reason_termination') }}</option>
                            <option value="resignation" @selected($reason === 'resignation')>{{ __('app.eosb.reason_resignation') }}</option>
                        </select>
                    </span>
                </label>
                <button class="primary-button calc-run" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    {{ __('app.eosb.calculate') }}
                </button>
            </div>
        </form>
    </section>

    @if (! $result)
        <div class="calc-empty">
            <div class="calc-empty-icon">🎗️</div>
            <strong>{{ __('app.eosb.calculator') }}</strong>
            <p>{{ __('app.eosb.pick_prompt') }}</p>
        </div>
    @else
        @php $e = $result; @endphp
        <div class="calc-summary">
            <div class="calc-summary-total">
                <span class="calc-summary-label">{{ __('app.eosb.award') }}</span>
                <strong class="tnum">{{ number_format($e['award'], 2) }}</strong>
                <small>{{ __('app.currency') }}</small>
            </div>
            <div class="calc-summary-split">
                <div class="calc-split-item late">
                    <span>{{ __('app.eosb.first_5') }}</span>
                    <strong class="tnum">{{ number_format($e['first_amount'], 2) }}</strong>
                </div>
                <div class="calc-split-item absence">
                    <span>{{ __('app.eosb.after_5') }}</span>
                    <strong class="tnum">{{ number_format($e['later_amount'], 2) }}</strong>
                </div>
            </div>
            <div class="calc-summary-meta">
                <div class="calc-meta-emp">
                    <strong>{{ $selectedEmployee->localizedName() }}</strong>
                    <span><bdi dir="ltr">{{ $e['start_date']->format('d/m/Y') }}</bdi> — <bdi dir="ltr">{{ $e['end_date']->format('d/m/Y') }}</bdi></span>
                </div>
                <span class="calc-policy-chip">{{ $e['reason'] === 'resignation' ? __('app.eosb.reason_resignation') : __('app.eosb.reason_termination') }}
                    @if ($e['reason'] === 'resignation') · {{ __('app.eosb.scale_'.$e['scale_label']) }} @endif
                </span>
                <div class="calc-rates">
                    <span>{{ __('app.eosb.wage') }} <b class="tnum">{{ number_format($e['wage'], 2) }}</b></span>
                    <span>{{ __('app.eosb.service') }} <b class="tnum">{{ $e['service']['years'] }}{{ __('app.eosb.y') }} {{ $e['service']['months'] }}{{ __('app.eosb.m') }} {{ $e['service']['days'] }}{{ __('app.eosb.d') }}</b></span>
                </div>
            </div>
        </div>

        <section class="panel">
            <div class="panel-header"><div><h2>{{ __('app.eosb.breakdown') }}</h2></div></div>
            <div class="table-wrap">
                <table>
                    <tbody>
                        <tr><td>{{ __('app.eosb.wage') }}</td><td class="money-value">{{ number_format($e['wage'], 2) }} {{ __('app.currency') }}</td></tr>
                        <tr><td>{{ __('app.eosb.total_years') }}</td><td class="tnum">{{ number_format($e['years'], 2) }}</td></tr>
                        <tr><td>{{ __('app.eosb.first_5') }} ({{ number_format($e['first_years'], 2) }} × ½)</td><td class="money-value">{{ number_format($e['first_amount'], 2) }}</td></tr>
                        <tr><td>{{ __('app.eosb.after_5') }} ({{ number_format($e['later_years'], 2) }} × 1)</td><td class="money-value">{{ number_format($e['later_amount'], 2) }}</td></tr>
                        <tr><td><strong>{{ __('app.eosb.base_award') }}</strong></td><td class="money-value"><strong>{{ number_format($e['base_award'], 2) }}</strong></td></tr>
                        @if ($e['reason'] === 'resignation')
                            <tr><td>{{ __('app.eosb.scale') }}</td><td>{{ __('app.eosb.scale_'.$e['scale_label']) }} (×{{ rtrim(rtrim(number_format($e['scale'], 4), '0'), '.') }})</td></tr>
                        @endif
                        <tr class="eosb-total-row"><td><strong>{{ __('app.eosb.award') }}</strong></td><td class="money-value"><strong>{{ number_format($e['award'], 2) }} {{ __('app.currency') }}</strong></td></tr>
                    </tbody>
                </table>
            </div>
            @unless ($e['eligible'])
                <p class="note eosb-warn">{{ __('app.eosb.not_eligible') }}</p>
            @endunless
        </section>
    @endif
@endsection
