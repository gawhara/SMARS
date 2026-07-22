@extends('layouts.app')

@section('title', __('app.dashboard'))

@php
    $statusMeta = [
        'present' => ['tone' => 'success', 'color' => '#16a34a'],
        'late' => ['tone' => 'info', 'color' => '#2563eb'],
        'half_day' => ['tone' => 'warning', 'color' => '#d97706'],
        'incomplete' => ['tone' => 'warning', 'color' => '#dc2626'],
        'leave' => ['tone' => 'muted', 'color' => '#7c3aed'],
        'holiday' => ['tone' => 'muted', 'color' => '#64748b'],
    ];
    $latestTotal = max(1, (int) $stats['latest_attendance']);
    $largestCompany = max(1, (int) $companies->max('employees_count'));
@endphp

@section('content')
    <section class="page-heading compact dashboard-heading">
        <div>
            <span class="eyebrow">{{ __('app.dash.operations') }}</span>
            <h1>{{ __('app.dash.title') }}</h1>
            <p>{{ __('app.dash.intro') }}</p>
        </div>
        <div class="dashboard-date-chip">
            <span>{{ __('app.dash.latest_attendance_day') }}</span>
            <strong dir="ltr">{{ $latestDate?->format('Y-m-d') ?? '—' }}</strong>
        </div>
    </section>

    <section class="dashboard-kpis">
        <a class="dashboard-kpi primary" href="{{ route('employees.index') }}">
            <span>{{ __('app.dash.total_employees') }}</span>
            <strong>{{ number_format($stats['employees']) }}</strong>
            <small>{{ __('app.dash.across_companies', ['count' => $stats['companies']]) }}</small>
        </a>
        <a class="dashboard-kpi" href="{{ route('attendance.index') }}">
            <span>{{ __('app.dash.latest_attendance') }}</span>
            <strong>{{ number_format($stats['latest_attendance']) }}</strong>
            <small>{{ $stats['attendance_coverage'] }}% {{ __('app.dash.coverage') }}</small>
        </a>
        <a class="dashboard-kpi" href="{{ route('attendance.reconciliation.index') }}">
            <span>{{ __('app.dash.exceptions') }}</span>
            <strong class="{{ $stats['open_exceptions'] ? 'tone-warning' : 'tone-success' }}">{{ number_format($stats['open_exceptions']) }}</strong>
            <small>{{ __('app.dash.require_review') }}</small>
        </a>
        <a class="dashboard-kpi" href="{{ route('attendance.index') }}">
            <span>{{ __('app.dash.total_punches') }}</span>
            <strong>{{ number_format($stats['punches']) }}</strong>
            <small>{{ $stats['matched_rate'] }}% {{ __('app.dash.matched') }}</small>
        </a>
        <a class="dashboard-kpi" href="{{ route('shifts.index') }}">
            <span>{{ __('app.dash.active_schedules') }}</span>
            <strong>{{ number_format($stats['active_schedules']) }}</strong>
            <small>{{ __('app.dash.shift_patterns') }}</small>
        </a>
    </section>

    <section class="dashboard-live-grid">
        <article class="panel dashboard-overview-panel">
            <div class="panel-header">
                <div><h2>{{ __('app.dash.attendance_overview') }}</h2><p>{{ __('app.dash.overview_for', ['date' => $latestDate?->format('Y-m-d') ?? '—']) }}</p></div>
                <span class="dashboard-coverage-ring" style="--coverage: {{ $stats['attendance_coverage'] * 3.6 }}deg"><strong>{{ $stats['attendance_coverage'] }}%</strong></span>
            </div>
            <div class="dashboard-status-list">
                @foreach (['present', 'late', 'half_day', 'incomplete', 'leave', 'holiday'] as $status)
                    @php $count = (int) ($latestStatusCounts[$status] ?? 0); $meta = $statusMeta[$status]; @endphp
                    <div class="dashboard-status-row">
                        <span>{{ __('app.att.summary_'.$status) }}</span>
                        <div class="dashboard-progress"><i style="width: {{ round($count / $latestTotal * 100) }}%; background: {{ $meta['color'] }}"></i></div>
                        <strong>{{ $count }}</strong>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel device-health-card">
            <div class="panel-header"><div><h2>{{ __('app.dash.device_health') }}</h2><p>{{ __('app.dash.real_device') }}</p></div></div>
            @if($machine)
                <div class="device-health-body">
                    <div class="device-health-top">
                        <span class="device-pulse {{ $machine->displayStatus() === 'online' ? 'online' : '' }}"></span>
                        <div><strong>{{ $machine->device_name }}</strong><small>{{ $machine->device_model }}</small></div>
                        <span class="status-badge {{ $machine->displayStatus() === 'online' ? 'success' : 'warning' }}">{{ __('app.device.status_'.$machine->displayStatus()) }}</span>
                    </div>
                    <dl class="device-health-details">
                        <div><dt>{{ __('app.device.ip_address') }}</dt><dd dir="ltr">{{ $machine->ip_address }}</dd></div>
                        <div><dt>{{ __('app.device.port') }}</dt><dd>{{ $machine->port }}</dd></div>
                        <div><dt>{{ __('app.dash.last_sync') }}</dt><dd>{{ $machine->last_sync_at?->diffForHumans() ?? __('app.dash.not_synced') }}</dd></div>
                        <div><dt>{{ __('app.dash.sync_mode') }}</dt><dd>{{ __('app.dash.read_only') }}</dd></div>
                    </dl>
                    <a class="ghost-button dashboard-card-action" href="{{ route('devices.show', $machine) }}">{{ __('app.dash.open_device') }}</a>
                </div>
            @else
                <div class="empty-state"><p>{{ __('app.dash.no_device') }}</p></div>
            @endif
        </article>
    </section>

    <section class="dashboard-live-grid dashboard-secondary-grid">
        <article class="panel">
            <div class="panel-header"><div><h2>{{ __('app.dash.company_distribution') }}</h2><p>{{ __('app.dash.company_distribution_intro') }}</p></div><a class="text-link" href="{{ route('companies.index') }}">{{ __('app.view') }}</a></div>
            <div class="company-distribution-list">
                @foreach($companies as $company)
                    <a href="{{ route('employees.index', ['company_id' => $company->id]) }}" class="company-distribution-row">
                        <span class="company-mini-mark">{{ mb_substr($company->name_en, 0, 1) }}</span>
                        <div><strong>{{ $company->localizedName() }}</strong><small>{{ $company->code }}</small></div>
                        <div class="company-bar"><i style="width: {{ round($company->employees_count / $largestCompany * 100) }}%"></i></div>
                        <strong>{{ $company->employees_count }}</strong>
                        <small>{{ $company->latest_attendance_count }} {{ __('app.dash.attended') }}</small>
                    </a>
                @endforeach
            </div>
        </article>

        <article class="panel">
            <div class="panel-header"><div><h2>{{ __('app.dash.recent_attendance') }}</h2><p>{{ __('app.dash.recent_attendance_intro') }}</p></div><a class="text-link" href="{{ route('attendance.index') }}">{{ __('app.view') }}</a></div>
            <div class="dashboard-recent-list">
                @forelse($recentSummaries as $summary)
                    <a href="{{ route('employees.show', $summary->employee) }}" class="dashboard-recent-row">
                        <span class="employee-avatar-small">{{ $summary->employee->initials() }}</span>
                        <div><strong>{{ $summary->employee->localizedName() }}</strong><small>{{ $summary->employee->hr_employee_id }} · {{ $summary->employee->company?->localizedName() }}</small></div>
                        <span dir="ltr">{{ $summary->localizedTime('first_in_at') }}</span>
                        <span class="status-badge {{ $summary->has_exception ? 'warning' : 'success' }}">{{ __('app.att.summary_'.$summary->status) }}</span>
                    </a>
                @empty
                    <div class="empty-state"><p>{{ __('app.att.no_daily') }}</p></div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="dashboard-quick-actions">
        <a href="{{ route('employees.create') }}"><strong>+</strong><span>{{ __('app.dash.add_employee') }}</span></a>
        <a href="{{ route('attendance.index') }}"><strong>↗</strong><span>{{ __('app.dash.review_attendance') }}</span></a>
        <a href="{{ route('attendance.reconciliation.index') }}"><strong>!</strong><span>{{ __('app.dash.resolve_exceptions') }}</span></a>
        <a href="{{ route('payroll.periods.index') }}"><strong>✓</strong><span>{{ __('app.dash.prepare_payroll') }}</span></a>
    </section>
@endsection
