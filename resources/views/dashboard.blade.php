@extends('layouts.app')

@section('title', __('app.dashboard'))

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.foundation') }}</span>
            <h1>{{ __('app.foundation_ready') }}</h1>
            <p>{{ __('app.foundation_note') }}</p>
        </div>
        <div class="heading-actions">
            <span class="status-badge info">{{ __('app.stitch_aligned') }}</span>
            <span class="status-badge success">{{ __('app.active') }}</span>
        </div>
    </section>

    <section class="stat-grid">
        <article class="stat-card accent">
            <span>{{ __('app.total_companies') }}</span>
            <strong>{{ $stats['companies'] }}</strong>
            <small>{{ __('app.central_hr') }}</small>
        </article>
        <article class="stat-card">
            <span>{{ __('app.active_companies') }}</span>
            <strong>{{ $stats['active_companies'] }}</strong>
            <small>{{ __('app.ready') }}</small>
        </article>
        <article class="stat-card">
            <span>{{ __('app.users') }}</span>
            <strong>{{ $stats['users'] }}</strong>
            <small>{{ __('app.protected') }}</small>
        </article>
        <article class="stat-card">
            <span>{{ __('app.roles') }}</span>
            <strong>{{ $stats['roles'] }}</strong>
            <small>{{ __('app.phase') }} 1</small>
        </article>
        <article class="stat-card">
            <span>{{ __('app.system_settings') }}</span>
            <strong>{{ $stats['settings'] }}</strong>
            <small>{{ __('app.database_ready') }}</small>
        </article>
    </section>

    <section class="status-strip">
        <article>
            <strong>{{ __('app.quick_status') }}</strong>
            <span>{{ __('app.offline_ready') }}</span>
        </article>
        <article>
            <strong>{{ __('app.local_font') }}</strong>
            <span>Cairo</span>
        </article>
        <article>
            <strong>{{ __('app.central_hr') }}</strong>
            <span>{{ __('app.central_hr_note') }}</span>
        </article>
    </section>

    <section class="dashboard-grid">
        <article class="panel">
            <div class="panel-header">
                <h2>{{ __('app.company_foundation') }}</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('app.code') }}</th>
                            <th>{{ __('app.name_ar') }}</th>
                            <th>{{ __('app.name_en') }}</th>
                            <th>{{ __('app.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>{{ $company->code }}</td>
                                <td>{{ $company->name_ar }}</td>
                                <td>{{ $company->name_en }}</td>
                                <td>
                                    <span class="status-badge {{ $company->is_active ? 'success' : 'muted' }}">
                                        {{ $company->is_active ? __('app.active') : __('app.inactive') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel">
            <div class="panel-header">
                <h2>{{ __('app.phase_1_scope') }}</h2>
            </div>
            <ul class="scope-list">
                @foreach (__('app.scope_items') as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </article>
    </section>

    <section class="panel future-panel">
        <div class="panel-header">
            <div>
                <h2>{{ __('app.future_dashboard') }}</h2>
                <p>{{ __('app.future_dashboard_note') }}</p>
            </div>
        </div>
        <div class="future-grid">
            @foreach (__('app.future_widgets') as $item)
                <div class="future-card">
                    <span>{{ $item }}</span>
                    <strong>0</strong>
                    <small>{{ __('app.not_built_yet') }}</small>
                </div>
            @endforeach
        </div>
    </section>
@endsection
