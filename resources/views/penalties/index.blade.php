@extends('layouts.app')

@section('title', __('app.penalty.title'))

@php
    $statusTone = ['active' => 'danger', 'cancelled' => 'muted'];
@endphp

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.payroll') }}</span>
            <h1>{{ __('app.penalty.title') }}</h1>
            <p>{{ __('app.penalty.intro') }}</p>
        </div>
    </section>

    @include('partials.flash')

    <div class="mini-stats">
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.penalty.stat_active') }}</span>
            <strong class="mini-stat-value">{{ number_format($stats['active']) }}</strong>
        </div>
        <div class="mini-stat">
            <span class="mini-stat-label">{{ __('app.penalty.stat_amount') }}</span>
            <strong class="mini-stat-value {{ $stats['amount'] > 0 ? 'text-danger' : '' }}" dir="ltr">{{ number_format($stats['amount'], 2) }}</strong>
        </div>
    </div>

    @can('penalties.manage')
        <section class="panel form-panel">
            <div class="panel-header"><h2>{{ __('app.penalty.add') }}</h2></div>
            <form method="POST" action="{{ route('penalties.store') }}" class="penalty-form">
                @csrf
                <label>
                    <span>{{ __('app.penalty.employee') }}</span>
                    <select name="employee_id" required>
                        <option value="">—</option>
                        @foreach ($employees as $e)
                            <option value="{{ $e->id }}" @selected(old('employee_id') == $e->id)>{{ $e->localizedName() }} · {{ $e->employee_code }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>{{ __('app.penalty.date') }}</span>
                    <input type="date" name="penalty_date" value="{{ old('penalty_date', now()->format('Y-m-d')) }}" required>
                </label>
                <label>
                    <span>{{ __('app.penalty.type') }}</span>
                    <select name="type" required>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" @selected(old('type') === $t)>{{ __('app.penalty.type_'.$t) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>{{ __('app.penalty.amount') }}</span>
                    <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount', 0) }}" dir="ltr">
                </label>
                <label class="penalty-reason">
                    <span>{{ __('app.penalty.reason') }}</span>
                    <input type="text" name="reason" value="{{ old('reason') }}" maxlength="255" required>
                </label>
                <button class="primary-button" type="submit">{{ __('app.penalty.add') }}</button>
            </form>
            <p class="muted-note" style="margin:12px 0 0">{{ __('app.penalty.reflects_note') }}</p>
        </section>
    @endcan

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <select name="company_id">
                <option value="">{{ __('app.all_companies') }}</option>
                @foreach ($companies as $c)
                    <option value="{{ $c->id }}" @selected((string) request('company_id') === (string) $c->id)>{{ $c->localizedName() }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">{{ __('app.all_statuses') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('app.penalty.status_active') }}</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>{{ __('app.penalty.status_cancelled') }}</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="{{ __('app.att.date_from') }}">
            <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="{{ __('app.att.date_to') }}">
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    @if ($penalties->count())
        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.penalty.date') }}</th>
                            <th>{{ __('app.penalty.employee') }}</th>
                            <th>{{ __('app.penalty.type') }}</th>
                            <th>{{ __('app.penalty.reason') }}</th>
                            <th>{{ __('app.penalty.amount') }}</th>
                            <th>{{ __('app.penalty.status') }}</th>
                            <th>{{ __('app.penalty.created_by') }}</th>
                            <th>{{ __('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penalties as $p)
                            <tr>
                                <td><bdi dir="ltr">{{ $p->penalty_date->format('d/m/Y') }}</bdi></td>
                                <td>
                                    <a class="cell-name" href="{{ route('employees.show', $p->employee) }}">{{ $p->employee?->localizedName() ?? __('app.none') }}</a>
                                    <small>{{ $p->employee?->employee_code }}</small>
                                </td>
                                <td><span class="status-badge info">{{ __('app.penalty.type_'.$p->type) }}</span></td>
                                <td>{{ $p->reason }}</td>
                                <td dir="ltr"><strong class="{{ $p->amount > 0 && $p->isActive() ? 'text-danger' : '' }}">{{ number_format((float) $p->amount, 2) }}</strong></td>
                                <td><span class="status-badge {{ $statusTone[$p->status] ?? 'muted' }}">{{ __('app.penalty.status_'.$p->status) }}</span></td>
                                <td>{{ $p->creator?->name ?? __('app.none') }}</td>
                                <td class="table-actions">
                                    @can('penalties.manage')
                                        @if ($p->isActive())
                                            <form method="POST" action="{{ route('penalties.cancel', $p) }}" onsubmit="return confirm('{{ __('app.penalty.confirm_cancel') }}')">
                                                @csrf @method('PUT')
                                                <button class="danger-button" type="submit">{{ __('app.penalty.cancel') }}</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <div class="company-grid-pagination">{{ $penalties->links() }}</div>
    @else
        <section class="panel empty-state">
            <span class="empty-icon">@include('partials.icon', ['name' => 'shield', 'class' => 'empty-icon-svg'])</span>
            <h3>{{ __('app.penalty.no_penalties') }}</h3>
        </section>
    @endif
@endsection
