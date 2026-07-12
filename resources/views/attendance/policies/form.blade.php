@extends('layouts.app')
@section('title', __('app.att.edit_policy'))
@section('content')
<section class="page-heading compact"><div><span class="eyebrow">{{ __('app.att.policies') }}</span><h1>{{ $company->localizedName() }}</h1><p>{{ __('app.att.policy_form_hint') }}</p></div></section>
<section class="panel form-panel"><form method="POST" action="{{ route('attendance.policies.update', $company) }}">@csrf @method('PUT')
<div class="form-grid">
@foreach(['grace_minutes','early_leave_grace_minutes','full_day_minutes','half_day_minutes','overtime_after_minutes'] as $field)
<label><span>{{ __('app.att.'.$field) }}</span><input type="number" name="{{ $field }}" min="0" value="{{ old($field, $policy->$field) }}" required>@error($field)<small>{{ $message }}</small>@enderror</label>
@endforeach
<label><span>{{ __('app.att.rounding_minutes') }}</span><select name="rounding_minutes">@foreach([1,5,10,15,30] as $value)<option value="{{ $value }}" @selected((int) old('rounding_minutes', $policy->rounding_minutes) === $value)>{{ $value }} {{ __('app.att.minutes') }}</option>@endforeach</select></label>
<fieldset class="form-span-2 policy-weekdays"><legend>{{ __('app.att.weekends') }}</legend>@foreach(range(0,6) as $day)<label class="check-row"><input type="checkbox" name="weekend_days[]" value="{{ $day }}" @checked(in_array($day, old('weekend_days', $policy->weekend_days ?? [5])))><span>{{ __('app.att.day_'.$day) }}</span></label>@endforeach @error('weekend_days')<small>{{ $message }}</small>@enderror</fieldset>
<label class="check-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $policy->is_active))><span>{{ __('app.active') }}</span></label>
</div><div class="form-actions"><button class="primary-button" type="submit">{{ __('app.save') }}</button><a class="ghost-button" href="{{ route('attendance.policies.index') }}">{{ __('app.cancel') }}</a></div></form></section>
@endsection
