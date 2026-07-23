@extends('layouts.app')

@section('title', __('app.audit.title'))

@section('content')
    <section class="page-heading compact">
        <div>
            <span class="eyebrow">{{ __('app.audit_logs') }}</span>
            <h1>{{ __('app.audit.title') }}</h1>
            <p>{{ __('app.audit.intro') }}</p>
        </div>
    </section>

    <section class="panel filter-panel">
        <form class="filter-bar" method="GET">
            <select name="action">
                <option value="">{{ __('app.audit.all_actions') }}</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ __('app.audit.act_'.str_replace('.', '_', $action)) }}</option>
                @endforeach
            </select>
            <select name="user_id">
                <option value="">{{ __('app.audit.all_users') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="{{ __('app.audit.time') }}">
            <input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="{{ __('app.audit.time') }}">
            <button class="primary-button" type="submit">{{ __('app.filters') }}</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div><h2>{{ __('app.audit.title') }}</h2><p>{{ $logs->total() }}</p></div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.audit.time') }}</th>
                        <th>{{ __('app.audit.user') }}</th>
                        <th>{{ __('app.audit.action') }}</th>
                        <th>{{ __('app.audit.subject') }}</th>
                        <th>{{ __('app.audit.details') }}</th>
                        <th>{{ __('app.audit.ip') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td dir="ltr">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $log->user?->name ?? __('app.audit.system') }}</td>
                            <td><span class="status-badge info">{{ $log->actionLabel() }}</span></td>
                            <td>{{ $log->description ?: __('app.none') }}</td>
                            <td>
                                @if (! empty($log->properties))
                                    <small class="muted-note">
                                        @foreach ($log->properties as $key => $value)
                                            @if (! is_null($value)){{ $key }}: {{ is_array($value) ? implode(', ', $value) : $value }}@if (! $loop->last) · @endif @endif
                                        @endforeach
                                    </small>
                                @else
                                    {{ __('app.none') }}
                                @endif
                            </td>
                            <td dir="ltr">{{ $log->ip_address ?: __('app.none') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">{{ __('app.audit.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </section>
@endsection
