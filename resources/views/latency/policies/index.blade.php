@extends('layouts.app')

@section('title', __('app.latency.policies'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.payroll') }}</span>
            <h1>{{ __('app.latency.policies') }}</h1>
            <p>{{ __('app.latency.policies_subtitle') }}</p>
        </div>
        <div class="table-actions">
            <a class="ghost-button" href="{{ route('latency.calculator') }}">{{ __('app.latency.calculator') }}</a>
            @can('payroll.manage')
                <button type="button" class="primary-button" data-dialog-open="new-policy-dialog">+ {{ __('app.latency.new_policy') }}</button>
            @endcan
        </div>
    </section>

    @include('partials.flash')

    @if ($policies->isEmpty())
        <div class="calc-empty">
            <div class="calc-empty-icon">⏱️</div>
            <strong>{{ __('app.latency.no_policies') }}</strong>
            @can('payroll.manage')<p><button type="button" class="link-button" data-dialog-open="new-policy-dialog">+ {{ __('app.latency.new_policy') }}</button></p>@endcan
        </div>
    @else
        <div class="policy-grid">
            @foreach ($policies as $policy)
                <article class="policy-card {{ $policy->is_default ? 'is-default' : '' }} {{ $policy->is_active ? '' : 'is-inactive' }}">
                    <header class="policy-card-head">
                        <h3>{{ $policy->name }}</h3>
                        <div class="policy-badges">
                            @if ($policy->is_default)<span class="status-badge success">{{ __('app.latency.default_badge') }}</span>@endif
                            @unless ($policy->is_active)<span class="status-badge muted">{{ __('app.latency.inactive_badge') }}</span>@endunless
                        </div>
                    </header>
                    <div class="policy-rule">
                        <span class="rule-pill">{{ __('app.latency.grace_minutes') }}: <b>{{ (int) $policy->grace_minutes }}</b></span>
                        <span class="rule-pill">{{ $policy->round_up_to_hour ? __('app.latency.round_hours') : __('app.latency.round_exact') }}</span>
                        <span class="rule-pill accent">×{{ rtrim(rtrim(number_format((float) $policy->multiplier, 2), '0'), '.') }}</span>
                    </div>
                    <footer class="policy-card-foot">
                        <span class="policy-assigned">{{ __('app.latency.assigned_employees') }}: <b>{{ number_format($policy->employees_count) }}</b></span>
                        @can('payroll.manage')
                            <div class="policy-actions">
                                <button type="button" class="ghost-button" data-dialog-open="edit-policy-{{ $policy->id }}">{{ __('app.edit') }}</button>
                                <form method="POST" action="{{ route('latency.policies.destroy', $policy) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ghost-button danger">{{ __('app.delete') }}</button>
                                </form>
                            </div>
                        @endcan
                    </footer>
                </article>
            @endforeach
        </div>
    @endif

    @can('payroll.manage')
        {{-- New policy dialog --}}
        <dialog id="new-policy-dialog" class="form-dialog">
            <form method="POST" action="{{ route('latency.policies.store') }}" class="latency-policy-form">
                @csrf
                <div class="form-dialog-head"><h3>{{ __('app.latency.new_policy') }}</h3></div>
                @include('latency.policies._fields', ['policy' => null])
                <div class="form-dialog-actions">
                    <button type="button" class="ghost-button" data-dialog-close>{{ __('app.cancel') }}</button>
                    <button type="submit" class="primary-button">{{ __('app.latency.save_policy') }}</button>
                </div>
            </form>
        </dialog>

        {{-- Edit dialogs (one per policy) --}}
        @foreach ($policies as $policy)
            <dialog id="edit-policy-{{ $policy->id }}" class="form-dialog">
                <form method="POST" action="{{ route('latency.policies.update', $policy) }}" class="latency-policy-form">
                    @csrf @method('PUT')
                    <div class="form-dialog-head"><h3>{{ $policy->name }}</h3></div>
                    @include('latency.policies._fields', ['policy' => $policy])
                    <div class="form-dialog-actions">
                        <button type="button" class="ghost-button" data-dialog-close>{{ __('app.cancel') }}</button>
                        <button type="submit" class="primary-button">{{ __('app.save') }}</button>
                    </div>
                </form>
            </dialog>
        @endforeach
    @endcan
@endsection
