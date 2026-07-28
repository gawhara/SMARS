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
        </div>
    </section>

    @include('partials.flash')

    @can('payroll.manage')
        <section class="panel">
            <div class="panel-header"><div><h2>{{ __('app.latency.new_policy') }}</h2></div></div>
            <form method="POST" action="{{ route('latency.policies.store') }}" class="latency-policy-form">
                @csrf
                @include('latency.policies._fields', ['policy' => null])
                <div class="form-actions"><button type="submit" class="primary-button">{{ __('app.latency.save_policy') }}</button></div>
            </form>
        </section>
    @endcan

    <section class="panel">
        <div class="panel-header"><div><h2>{{ __('app.latency.policies') }}</h2><p>{{ $policies->count() }}</p></div></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.latency.name') }}</th>
                        <th>{{ __('app.latency.rule_summary') }}</th>
                        <th>{{ __('app.latency.assigned_employees') }}</th>
                        <th>{{ __('app.status') }}</th>
                        @can('payroll.manage')<th>{{ __('app.actions') }}</th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($policies as $policy)
                        <tr>
                            <td><strong>{{ $policy->name }}</strong></td>
                            <td dir="ltr">{{ __('app.latency.rule_line', ['grace' => (int) $policy->grace_minutes, 'round' => $policy->round_up_to_hour ? __('app.latency.round_hours') : __('app.latency.round_exact'), 'mult' => rtrim(rtrim(number_format((float) $policy->multiplier, 2), '0'), '.')]) }}</td>
                            <td>{{ number_format($policy->employees_count) }}</td>
                            <td>
                                @if ($policy->is_default)<span class="status-badge success">{{ __('app.latency.default_badge') }}</span>@endif
                                @unless ($policy->is_active)<span class="status-badge muted">{{ __('app.latency.inactive_badge') }}</span>@endunless
                            </td>
                            @can('payroll.manage')
                                <td>
                                    <details class="row-editor">
                                        <summary class="ghost-button">{{ __('app.edit') }}</summary>
                                        <form method="POST" action="{{ route('latency.policies.update', $policy) }}" class="latency-policy-form">
                                            @csrf @method('PUT')
                                            @include('latency.policies._fields', ['policy' => $policy])
                                            <div class="form-actions"><button type="submit" class="primary-button">{{ __('app.save') }}</button></div>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('latency.policies.destroy', $policy) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')" style="display:inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="ghost-button danger">{{ __('app.delete') }}</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-row">{{ __('app.latency.no_policies') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
