@extends('layouts.app')

@section('title', $employee->localizedName())

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.att.employee_attendance') }}</span>
            <h1>{{ $employee->localizedName() }}</h1>
            <p>{{ $employee->employee_code }} · {{ $employee->company?->localizedName() ?? __('app.none') }}
                @if ($employee->department) · {{ $employee->department->localizedName() }} @endif
            </p>
        </div>
        <div class="table-actions">
            <button type="button" class="primary-button" data-dialog-open="print-range-dialog">{{ __('app.att.print_report') }}</button>
            <a class="ghost-button" href="{{ route('attendance.index') }}">{{ __('app.att.back_to_directory') }}</a>
            <a class="ghost-button" href="{{ route('employees.show', $employee) }}">{{ __('app.att.employee_profile') }}</a>
        </div>
    </section>

    {{-- Pick the report period before printing --}}
    <dialog id="print-range-dialog" class="range-dialog">
        <form method="GET" action="{{ route('attendance.employee.print', $employee) }}" target="_blank">
            <h3>{{ __('app.att.print_report') }}</h3>
            <p>{{ __('app.att.choose_print_range') }}</p>
            <div class="att-field-row">
                <label class="att-field">
                    <span>{{ __('app.att.date_from') }}</span>
                    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" required>
                </label>
                <label class="att-field">
                    <span>{{ __('app.att.date_to') }}</span>
                    <input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}" required>
                </label>
            </div>
            <div class="range-dialog-actions">
                <button type="button" class="ghost-button" data-dialog-close>{{ __('app.cancel') }}</button>
                <button type="submit" class="primary-button" data-dialog-close>{{ __('app.att.print_report') }}</button>
            </div>
        </form>
    </dialog>

    @include('partials.flash')

    @php
        $expected = $summary['worked_days'] + $summary['absent'];
        $rate = $expected > 0 ? (int) round($summary['worked_days'] / $expected * 100) : 100;
        $ringColor = $rate >= 90 ? '#10b981' : ($rate >= 75 ? '#f59e0b' : '#ef4444');
        $today = now();
        $presets = [
            'this_month' => [$today->copy()->startOfMonth(), $today],
            'last_30' => [$today->copy()->subDays(29), $today],
            'this_year' => [$today->copy()->startOfYear(), $today],
            'since_joining' => [$employee->start_date ? \Carbon\Carbon::parse($employee->start_date) : $today->copy()->startOfYear(), $today],
        ];
    @endphp

    {{-- Compact console: attendance rate + period filter + quick presets --}}
    <section class="att-console">
        <div class="att-console-rate">
            <span class="att-rate-ring" style="--rate: {{ $rate * 3.6 }}deg; --ring-color: {{ $ringColor }}">
                <strong>{{ $rate }}%</strong>
            </span>
            <div class="att-rate-meta">
                <span class="att-rate-label">{{ __('app.att.attendance_rate') }}</span>
                <h2>{{ __('app.att.worked_of_expected', ['worked' => $summary['worked_days'], 'expected' => $expected]) }}</h2>
                <p><bdi dir="ltr">{{ $from->format('d/m/Y') }}</bdi> — <bdi dir="ltr">{{ $to->format('d/m/Y') }}</bdi></p>
            </div>
        </div>
        <form class="att-console-filter" method="GET">
            <div class="att-field-row">
                <label class="att-field">
                    <span>{{ __('app.att.date_from') }}</span>
                    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}">
                </label>
                <label class="att-field">
                    <span>{{ __('app.att.date_to') }}</span>
                    <input type="date" name="date_to" value="{{ $to->format('Y-m-d') }}">
                </label>
                <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
            </div>
            <div class="att-presets">
                @foreach ($presets as $key => [$pFrom, $pTo])
                    @php $isActive = $from->isSameDay($pFrom) && $to->isSameDay($pTo); @endphp
                    <a class="att-preset {{ $isActive ? 'active' : '' }}"
                       href="{{ route('attendance.employee', ['employee' => $employee->id, 'date_from' => $pFrom->format('Y-m-d'), 'date_to' => $pTo->format('Y-m-d')]) }}">
                        {{ __('app.att.preset_'.$key) }}
                    </a>
                @endforeach
            </div>
        </form>
    </section>

    {{-- Dashboard: attendance / absence / leaves over the period --}}
    <div class="att-metrics">
        <div class="att-metric" style="--m: #10b981">
            <span class="att-metric-label">{{ __('app.att.present_days') }}</span>
            <span class="att-metric-value">{{ number_format($summary['present']) }}</span>
        </div>
        <div class="att-metric" style="--m: #f59e0b">
            <span class="att-metric-label">{{ __('app.att.late_days') }}</span>
            <span class="att-metric-value">{{ number_format($summary['late']) }}</span>
        </div>
        <div class="att-metric" style="--m: #ef4444">
            <span class="att-metric-label">{{ __('app.att.absent_days') }}</span>
            <span class="att-metric-value">{{ number_format($summary['absent']) }}</span>
        </div>
        <div class="att-metric" style="--m: #8b5cf6">
            <span class="att-metric-label">{{ __('app.att.leave_days') }}</span>
            <span class="att-metric-value">{{ number_format($summary['leave']) }}</span>
        </div>
        <div class="att-metric" style="--m: #64748b">
            <span class="att-metric-label">{{ __('app.att.holiday_days') }}</span>
            <span class="att-metric-value">{{ number_format($summary['holiday']) }}</span>
        </div>
        <div class="att-metric" style="--m: #14b8a6">
            <span class="att-metric-label">{{ __('app.att.worked_hours') }}</span>
            <span class="att-metric-value">{{ number_format($summary['hours'], 1) }}</span>
        </div>
        <div class="att-metric" style="--m: #3b82f6">
            <span class="att-metric-label">{{ __('app.att.total_punches') }}</span>
            <span class="att-metric-value">{{ number_format($summary['punches']) }}</span>
        </div>
    </div>

    {{-- Approved leaves within the period --}}
    @if ($leaves->isNotEmpty())
        <section class="panel">
            <div class="panel-header"><div><h2>{{ __('app.att.leaves_in_period') }}</h2><p>{{ $leaves->count() }}</p></div></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.att.date_from') }}</th>
                            <th>{{ __('app.att.date_to') }}</th>
                            <th>{{ __('app.att.leave_days') }}</th>
                            <th>{{ __('app.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaves as $leave)
                            <tr>
                                <td><bdi dir="ltr">{{ $leave->start_date->format('d/m/Y') }}</bdi></td>
                                <td><bdi dir="ltr">{{ $leave->end_date->format('d/m/Y') }}</bdi></td>
                                <td>{{ $leave->start_date->diffInDays($leave->end_date) + 1 }}</td>
                                <td><span class="status-badge success">{{ __('app.att.summary_leave') }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- Day-by-day punch record --}}
    <section class="panel">
        <div class="panel-header"><div><h2>{{ __('app.att.daily_record') }}</h2><p>{{ $days->total() }}</p></div></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.att.date') }}</th>
                        <th>{{ __('app.att.first_in') }}</th>
                        <th>{{ __('app.att.last_out') }}</th>
                        <th>{{ __('app.att.actual_scheduled') }}</th>
                        <th>{{ __('app.att.punches') }}</th>
                        <th>{{ __('app.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($days as $day)
                        <tr>
                            <td><strong><bdi dir="ltr">{{ $day->attendance_date->format('d/m/Y') }}</bdi></strong></td>
                            <td><bdi dir="ltr">{{ $day->localizedTime('first_in_at') }}</bdi></td>
                            <td><bdi dir="ltr">{{ $day->localizedTime('last_out_at') }}</bdi></td>
                            <td><bdi dir="ltr"><strong>{{ number_format($day->worked_minutes / 60, 2) }}</strong> / {{ number_format($day->scheduled_minutes / 60, 2) }}</bdi></td>
                            <td>{{ $day->punch_count }}</td>
                            <td><span class="status-badge {{ $day->has_exception ? 'warning' : ($day->status === 'late' ? 'info' : 'success') }}">{{ __('app.att.summary_'.$day->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">{{ __('app.att.no_days') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="company-grid-pagination">{{ $days->links() }}</div>
@endsection
