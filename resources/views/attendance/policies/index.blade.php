@extends('layouts.app')
@section('title', __('app.att.policies'))
@section('content')
<section class="page-heading compact"><div><span class="eyebrow">{{ __('app.attendance') }}</span><h1>{{ __('app.att.policies') }}</h1><p>{{ __('app.att.policy_intro') }}</p></div><a class="ghost-button" href="{{ route('attendance.index') }}">{{ __('app.att.back_to_log') }}</a></section>
@include('partials.flash')
<section class="panel"><div class="table-wrap"><table><thead><tr><th>{{ __('app.company') }}</th><th>{{ __('app.att.grace_minutes') }}</th><th>{{ __('app.att.full_day') }}</th><th>{{ __('app.att.half_day') }}</th><th>{{ __('app.att.overtime_after') }}</th><th>{{ __('app.att.weekends') }}</th><th>{{ __('app.actions') }}</th></tr></thead><tbody>
@foreach($companies as $company) @php($policy = $company->attendancePolicy ?? \App\Models\AttendancePolicy::defaults($company->id))
<tr><td><strong>{{ $company->localizedName() }}</strong><small>{{ $company->code }}</small></td><td>{{ $policy->grace_minutes }} {{ __('app.att.minutes') }}</td><td>{{ number_format($policy->full_day_minutes / 60, 1) }}</td><td>{{ number_format($policy->half_day_minutes / 60, 1) }}</td><td>{{ number_format($policy->overtime_after_minutes / 60, 1) }}</td><td>{{ collect($policy->weekend_days ?? [5])->map(fn($day) => __('app.att.day_'.$day))->join('، ') }}</td><td><a class="ghost-button" href="{{ route('attendance.policies.edit', $company) }}">{{ __('app.edit') }}</a></td></tr>
@endforeach
</tbody></table></div></section>
@endsection
