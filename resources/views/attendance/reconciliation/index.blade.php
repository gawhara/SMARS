@extends('layouts.app')

@section('title', __('app.recon.title'))

@php
    $formatTime = static function ($value): string {
        if (! $value) return '—';
        $time = $value instanceof \Carbon\Carbon ? $value : \Carbon\Carbon::parse($value);
        return app()->isLocale('ar')
            ? $time->format('h:i').' '.($time->format('A') === 'AM' ? __('app.time_am') : __('app.time_pm'))
            : $time->format('h:i A');
    };
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.attendance') }}</span>
            <h1>{{ __('app.recon.title') }}</h1>
            <p>{{ __('app.recon.intro') }}</p>
        </div>
        <div class="table-actions">
            <a class="ghost-button" href="{{ route('attendance.corrections.index') }}">{{ __('app.att.corrections') }} <span class="button-count">{{ $stats['corrections'] }}</span></a>
            <a class="ghost-button" href="{{ route('attendance.index') }}">{{ __('app.att.back_to_log') }}</a>
        </div>
    </section>

    @include('partials.flash')

    <div class="reconciliation-stats">
        <a href="{{ route('attendance.reconciliation.index', ['status' => 'open']) }}" class="reconciliation-stat {{ $status === 'open' ? 'active' : '' }}">
            <span>{{ __('app.recon.open_queue') }}</span><strong>{{ number_format($stats['open']) }}</strong><small>{{ __('app.recon.awaiting_decision') }}</small>
        </a>
        <a href="{{ route('attendance.reconciliation.index', ['status' => 'approved']) }}" class="reconciliation-stat {{ $status === 'approved' ? 'active' : '' }}">
            <span>{{ __('app.recon.approved') }}</span><strong>{{ number_format($stats['approved']) }}</strong><small>{{ __('app.recon.accepted_exceptions') }}</small>
        </a>
        <div class="reconciliation-stat"><span>{{ __('app.recon.approved_today') }}</span><strong>{{ number_format($stats['approved_today']) }}</strong><small>{{ __('app.recon.by_reviewers') }}</small></div>
        <a href="{{ route('attendance.corrections.index', ['status' => 'pending']) }}" class="reconciliation-stat"><span>{{ __('app.recon.pending_corrections') }}</span><strong>{{ number_format($stats['corrections']) }}</strong><small>{{ __('app.recon.awaiting_correction') }}</small></a>
    </div>

    <section class="panel filter-panel reconciliation-filter-panel">
        <form class="filter-bar reconciliation-filter-bar" method="GET">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('app.recon.search_placeholder') }}">
            <select name="company_id"><option value="">{{ __('app.all_companies') }}</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)request('company_id') === (string)$company->id)>{{ $company->localizedName() }}</option>@endforeach</select>
            <select name="employee_id"><option value="">{{ __('app.att.all_employees') }}</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string)request('employee_id') === (string)$employee->id)>{{ $employee->localizedName() }} · {{ $employee->hr_employee_id }}</option>@endforeach</select>
            <select name="exception"><option value="">{{ __('app.att.all_exceptions') }}</option>@foreach($exceptionTypes as $type)<option value="{{ $type }}" @selected(request('exception') === $type)>{{ __('app.att.ex_'.$type) }}</option>@endforeach</select>
            <select name="status"><option value="open" @selected($status === 'open')>{{ __('app.recon.open') }}</option><option value="approved" @selected($status === 'approved')>{{ __('app.recon.approved') }}</option><option value="all" @selected($status === 'all')>{{ __('app.all_statuses') }}</option></select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="{{ __('app.att.date_from') }}">
            <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="{{ __('app.att.date_to') }}">
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    <form method="POST" action="{{ route('attendance.reconciliation.approve') }}" data-reconciliation-form>
        @csrf
        @method('PUT')
        <section class="reconciliation-bulk-bar">
            <div><strong><span data-selected-count>0</span> {{ __('app.recon.selected') }}</strong><small>{{ __('app.recon.bulk_hint') }}</small></div>
            <input name="notes" maxlength="1000" placeholder="{{ __('app.recon.notes_placeholder') }}">
            <button class="ghost-button" type="submit" formaction="{{ route('attendance.reconciliation.reopen') }}" data-bulk-action disabled>{{ __('app.recon.reopen') }}</button>
            <button class="primary-button" type="submit" data-bulk-action disabled>{{ __('app.recon.approve_selected') }}</button>
        </section>

        <section class="panel reconciliation-table-panel">
            <div class="panel-header">
                <div><h2>{{ __('app.recon.queue') }}</h2><p>{{ $summaries->total() }} {{ __('app.recon.filtered_records') }}</p></div>
                <span class="status-badge {{ $status === 'approved' ? 'success' : 'warning' }}">{{ __('app.recon.'.$status) }}</span>
            </div>
            <div class="table-wrap">
                <table class="reconciliation-table">
                    <thead><tr>
                        <th class="select-column"><input type="checkbox" data-select-all aria-label="{{ __('app.recon.select_all') }}"></th>
                        <th>{{ __('app.att.employee') }}</th><th>{{ __('app.att.date') }}</th><th>{{ __('app.recon.shift_punches') }}</th><th>{{ __('app.att.hours') }}</th><th>{{ __('app.att.exception_reason') }}</th><th>{{ __('app.recon.review_status') }}</th><th>{{ __('app.actions') }}</th>
                    </tr></thead>
                    <tbody>
                        @forelse($summaries as $row)
                            @php $summary = $row['summary']; @endphp
                            <tr class="{{ ($row['locked'] || $row['pending_correction']) ? 'reconciliation-row-locked' : '' }}">
                                <td class="select-column"><input type="checkbox" name="summary_ids[]" value="{{ $summary->id }}" data-row-select @disabled($row['locked'] || $row['pending_correction']) aria-label="{{ __('app.recon.select_record', ['name' => $summary->employee->localizedName()]) }}"></td>
                                <td><a class="cell-name" href="{{ route('employees.show', $summary->employee) }}">{{ $summary->employee->localizedName() }}</a><small>{{ $summary->employee->hr_employee_id }} · {{ $summary->employee->company?->localizedName() }}</small></td>
                                <td><strong dir="ltr">{{ $summary->attendance_date->format('d/m/Y') }}</strong><small>{{ $summary->employee->shift?->localizedName() ?? __('app.none') }}</small></td>
                                <td>
                                    <div class="reconciliation-periods">
                                        @forelse($row['periods'] as $period)
                                            <div><span>{{ __('app.work_period_'.$period['number']) }}</span><strong dir="ltr">{{ $formatTime($period['actual_in']) }} → {{ $formatTime($period['actual_out']) }}</strong><small dir="ltr">{{ $formatTime($period['scheduled_in']) }} → {{ $formatTime($period['scheduled_out']) }}</small></div>
                                        @empty<span>—</span>@endforelse
                                    </div>
                                    <details class="raw-punch-details"><summary>{{ $row['punches']->count() }} {{ __('app.att.punches') }}</summary><div>@foreach($row['punches'] as $punch)<span class="raw-punch-chip {{ $punch->punch_type }}"><b>{{ __('app.att.punch_'.$punch->punch_type) }}</b><time dir="ltr">{{ $formatTime($punch->punch_at) }}</time></span>@endforeach</div></details>
                                </td>
                                <td><strong>{{ number_format($summary->worked_minutes / 60, 2) }}</strong><small>/ {{ number_format($summary->scheduled_minutes / 60, 2) }}</small></td>
                                <td><div class="exception-tags">@foreach($summary->exception_codes ?? [] as $code)<span class="status-badge warning">{{ __('app.att.ex_'.$code) }}</span>@endforeach</div></td>
                                <td>
                                    @if($row['locked'])<span class="status-badge muted">{{ __('app.recon.locked') }}</span>
                                    @elseif($row['pending_correction'])<span class="status-badge info">{{ __('app.recon.correction_pending') }}</span>
                                    @elseif($summary->reconciliation_status === 'approved')<span class="status-badge success">{{ __('app.recon.approved') }}</span><small>{{ $summary->reconciler?->name }} · {{ $summary->reconciled_at?->format('d/m/Y') }}</small>
                                    @else<span class="status-badge warning">{{ __('app.recon.open') }}</span>@endif
                                </td>
                                <td class="table-actions"><a class="ghost-button" href="{{ route('attendance.index', ['employee_id' => $summary->employee_id, 'date_from' => $summary->attendance_date->format('Y-m-d'), 'date_to' => $summary->attendance_date->format('Y-m-d')]) }}">{{ __('app.recon.view_day') }}</a><a class="primary-button" href="{{ route('attendance.corrections.create', ['employee_id' => $summary->employee_id, 'date' => $summary->attendance_date->format('Y-m-d')]) }}">{{ __('app.att.add_correction') }}</a></td>
                            </tr>
                        @empty<tr><td colspan="8" class="empty-row">{{ __('app.recon.empty') }}</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </form>
    <div class="company-grid-pagination">{{ $summaries->links() }}</div>
@endsection
