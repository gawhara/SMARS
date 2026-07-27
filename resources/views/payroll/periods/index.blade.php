@extends('layouts.app')

@section('title', __('app.pay.title'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.payroll') }}</span>
            <h1>{{ __('app.pay.title') }}</h1>
        </div>
    </section>

    @include('partials.flash')

    @can('payroll.manage')
    <section class="panel form-panel">
        <p class="muted-note" style="margin-top:0">{{ __('app.pay.intro') }}</p>
        <form method="POST" action="{{ route('payroll.periods.store') }}" class="copy-form">
            @csrf
            <label>
                <span>{{ __('app.pay.company') }}</span>
                <select name="company_id" required>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->localizedName() }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>{{ __('app.pay.month') }}</span>
                <input type="month" name="period_month" value="{{ now()->format('Y-m') }}" required>
            </label>
            <button class="primary-button" type="submit">{{ __('app.pay.add_period') }}</button>
        </form>
    </section>
    @endcan

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.pay.company') }}</th>
                        <th>{{ __('app.pay.period') }}</th>
                        <th>{{ __('app.pay.status') }}</th>
                        <th>{{ __('app.pay.locked_by') }}</th>
                        <th>{{ __('app.pay.exported_at') }}</th>
                        <th>{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($periods as $period)
                        <tr>
                            <td>{{ $period->company?->localizedName() }}</td>
                            <td><strong>{{ $period->label() }}</strong></td>
                            <td>
                                <span class="status-badge {{ $period->isLocked() ? 'danger' : 'success' }}">
                                    {{ $period->isLocked() ? __('app.pay.locked') : __('app.pay.open') }}
                                </span>
                            </td>
                            <td>
                                @if ($period->isLocked())
                                    {{ $period->lockedBy?->name ?? __('app.none') }}
                                    <small>{{ optional($period->locked_at)->format('Y-m-d H:i') }}</small>
                                @else
                                    {{ __('app.none') }}
                                @endif
                            </td>
                            <td>{{ optional($period->exported_at)->format('Y-m-d H:i') ?? __('app.pay.never') }}</td>
                            <td class="table-actions">
                                <a class="ghost-button" href="{{ route('payroll.deductions.index', ['company_id' => $period->company_id, 'date_from' => \Carbon\Carbon::parse($period->period_month)->startOfMonth()->format('Y-m-d'), 'date_to' => \Carbon\Carbon::parse($period->period_month)->endOfMonth()->format('Y-m-d')]) }}">{{ __('app.deduct.title') }}</a>
                                @can('payroll.manage')
                                    <a class="ghost-button" href="{{ route('payroll.periods.export', $period) }}">{{ __('app.pay.export') }}</a>
                                    @if ($period->isLocked())
                                        <form method="POST" action="{{ route('payroll.periods.unlock', $period) }}">
                                            @csrf @method('PUT')
                                            <button class="ghost-button" type="submit">{{ __('app.pay.unlock') }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('payroll.periods.lock', $period) }}" onsubmit="return confirm('{{ __('app.pay.confirm_lock') }}')">
                                            @csrf @method('PUT')
                                            <button class="danger-button" type="submit">{{ __('app.pay.lock') }}</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">{{ __('app.none') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
