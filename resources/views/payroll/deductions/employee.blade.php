@extends('layouts.app')

@section('title', $employee->localizedName().' — '.__('app.deduct.title'))

@php
    $tone = [
        'present' => 'success', 'late' => 'info', 'absent' => 'danger',
        'missing_in' => 'warning', 'missing_out' => 'warning', 'unresolved' => 'warning',
        'partial' => 'warning', 'incomplete' => 'warning',
        'rest' => 'muted', 'holiday' => 'muted', 'leave' => 'muted', 'no_shift' => 'muted',
    ];
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.deduct.title') }} · <bdi dir="ltr">{{ $from->format('d/m/Y') }} → {{ $to->format('d/m/Y') }}</bdi></span>
            <h1>{{ $employee->localizedName() }}</h1>
            <p>{{ $employee->employee_code }} · {{ $employee->company?->localizedName() ?? __('app.none') }}</p>
        </div>
        <a class="ghost-button" href="{{ route('payroll.deductions.index', ['company_id' => $employee->company_id, 'date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]) }}">{{ __('app.att.back_to_directory') }}</a>
    </section>

    @include('partials.flash')

    {{-- Rate basis (sections 26–28, 32) --}}
    <div class="mini-stats">
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.deduct.salary_basis') }}</span>
            <strong class="mini-stat-value" dir="ltr">{{ number_format($report['salary_basis'], 2) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.deduct.scheduled_hours') }}</span>
            <strong class="mini-stat-value">{{ number_format($report['scheduled_hours'], 1) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.deduct.hourly_rate') }}</span>
            <strong class="mini-stat-value" dir="ltr">{{ number_format($report['hourly_rate'], 2) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.deduct.daily_rate') }}</span>
            <strong class="mini-stat-value" dir="ltr">{{ number_format($report['daily_rate'], 2) }}</strong>
        </div>
    </div>

    {{-- Deduction totals (sections 29–33, 35) --}}
    <div class="att-metrics">
        <div class="att-metric" style="--m: #f59e0b">
            <span class="att-metric-label">{{ __('app.deduct.late_hours') }}</span>
            <span class="att-metric-value">{{ $report['late_hours'] }}<small style="font-size:.7rem;color:var(--muted)"> · {{ number_format($report['late_amount'], 2) }}</small></span>
        </div>
        <div class="att-metric" style="--m: #8b5cf6">
            <span class="att-metric-label">{{ __('app.deduct.early_hours') }}</span>
            <span class="att-metric-value">{{ $report['early_hours'] }}<small style="font-size:.7rem;color:var(--muted)"> · {{ number_format($report['early_amount'], 2) }}</small></span>
        </div>
        <div class="att-metric" style="--m: #64748b">
            <span class="att-metric-label">{{ __('app.deduct.missing_hours') }}</span>
            <span class="att-metric-value">{{ $report['missing_hours'] }}<small style="font-size:.7rem;color:var(--muted)"> · {{ number_format($report['missing_amount'], 2) }}</small></span>
        </div>
        <div class="att-metric" style="--m: #ef4444">
            <span class="att-metric-label">{{ __('app.deduct.absence_days') }}</span>
            <span class="att-metric-value">{{ $report['penalty_days'] }}<small style="font-size:.7rem;color:var(--muted)"> · {{ number_format($report['absence_amount'], 2) }}</small></span>
        </div>
        <div class="att-metric" style="--m: #db2777">
            <span class="att-metric-label">{{ __('app.penalty.title') }}</span>
            <span class="att-metric-value">{{ $report['penalty_count'] ?? 0 }}<small style="font-size:.7rem;color:var(--muted)"> · {{ number_format($report['penalty_amount'] ?? 0, 2) }}</small></span>
        </div>
        <div class="att-metric" style="--m: #dc2626">
            <span class="att-metric-label">{{ __('app.deduct.total_deduction') }}</span>
            <span class="att-metric-value" dir="ltr">{{ number_format($report['total_deduction'], 2) }}</span>
        </div>
        <div class="att-metric" style="--m: #059669">
            <span class="att-metric-label">{{ __('app.deduct.net_salary') }}</span>
            <span class="att-metric-value" dir="ltr">{{ number_format($report['net_salary'], 2) }}</span>
        </div>
    </div>

    {{-- Day-by-day breakdown (section 38) --}}
    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.deduct.day') }}</th>
                        <th>{{ __('app.status') }}</th>
                        <th>{{ __('app.deduct.scheduled') }} → {{ __('app.deduct.actual') }}</th>
                        <th>{{ __('app.deduct.late_min') }}</th>
                        <th>{{ __('app.deduct.early_min') }}</th>
                        <th>{{ __('app.deduct.missing_hours') }}</th>
                        <th>{{ __('app.deduct.absence_days') }}</th>
                        <th>{{ __('app.deduct.deduction_hours') }}</th>
                        <th>{{ __('app.deduct.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['days'] as $day)
                        @php
                            $dayLate = array_sum(array_column($day['shifts'], 'late_deduction_hours'));
                            $dayEarly = array_sum(array_column($day['shifts'], 'early_deduction_hours'));
                            $dayMissing = array_sum(array_column($day['shifts'], 'missing_punch_hours'));
                            $dayHours = $dayLate + $dayEarly + $dayMissing;
                            $dayAmount = $day['amounts']['late'] + $day['amounts']['early'] + $day['amounts']['missing'] + $day['amounts']['absence'];
                        @endphp
                        <tr>
                            <td><bdi dir="ltr">{{ $day['date'] }}</bdi></td>
                            <td>
                                <span class="status-badge {{ $tone[$day['status']] ?? 'muted' }}">{{ __('app.deduct.st_'.$day['status']) }}</span>
                                @if ($day['needs_review'])<small class="tone-warning">{{ __('app.deduct.needs_review') }}</small>@endif
                            </td>
                            <td>
                                @foreach ($day['shifts'] as $s)
                                    <div class="shift-line" dir="ltr">
                                        {{ $s['scheduled_in'] }}–{{ $s['scheduled_out'] }} →
                                        {{ $s['actual_in'] ?? '—' }}–{{ $s['actual_out'] ?? '—' }}
                                    </div>
                                @endforeach
                            </td>
                            <td>{{ array_sum(array_column($day['shifts'], 'late_minutes')) ?: '—' }}</td>
                            <td>{{ array_sum(array_column($day['shifts'], 'early_minutes')) ?: '—' }}</td>
                            <td>{{ $dayMissing ?: '—' }}</td>
                            <td class="{{ $day['absence']['penalty_days'] > 0 ? 'text-danger' : '' }}">{{ $day['absence']['penalty_days'] ?: '—' }}</td>
                            <td><strong>{{ $dayHours ?: '—' }}</strong></td>
                            <td dir="ltr"><strong class="{{ $dayAmount > 0 ? 'text-danger' : '' }}">{{ $dayAmount > 0 ? number_format($dayAmount, 2) : '—' }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-row">{{ __('app.deduct.no_deductions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
