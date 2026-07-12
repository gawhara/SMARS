@extends('layouts.app')
@section('title', __('app.att.request_correction'))
@section('content')
<section class="page-heading compact"><div><span class="eyebrow">{{ __('app.att.corrections') }}</span><h1>{{ __('app.att.request_correction') }}</h1><p>{{ __('app.att.correction_form_hint') }}</p></div></section>
<section class="panel form-panel"><form method="POST" action="{{ route('attendance.corrections.store') }}">@csrf
<div class="form-grid">
<label><span>{{ __('app.att.employee') }}</span><select name="employee_id" required><option value="">{{ __('app.select_placeholder') }}</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) old('employee_id', $record?->employee_id ?? request('employee_id')) === (string) $employee->id)>{{ $employee->localizedName() }} · {{ $employee->employee_code }}</option>@endforeach</select>@error('employee_id')<small>{{ $message }}</small>@enderror</label>
<input type="hidden" name="attendance_record_id" value="{{ old('attendance_record_id', $record?->id) }}">
<label><span>{{ __('app.att.requested_time') }}</span><input type="datetime-local" name="requested_punch_at" value="{{ old('requested_punch_at', $record?->punch_at?->format('Y-m-d\TH:i') ?? (request('date') ? request('date').'T08:00' : '')) }}" required>@error('requested_punch_at')<small>{{ $message }}</small>@enderror</label>
<label><span>{{ __('app.att.requested_type') }}</span><select name="requested_punch_type" required>@foreach(['in','out'] as $type)<option value="{{ $type }}" @selected(old('requested_punch_type', $record?->punch_type ?? 'in') === $type)>{{ __('app.att.punch_'.$type) }}</option>@endforeach</select></label>
<label class="form-span-2"><span>{{ __('app.att.correction_reason') }}</span><textarea name="reason" rows="4" required>{{ old('reason') }}</textarea>@error('reason')<small>{{ $message }}</small>@enderror</label>
</div><div class="form-actions"><button class="primary-button" type="submit">{{ __('app.att.submit_correction') }}</button><a class="ghost-button" href="{{ route('attendance.corrections.index') }}">{{ __('app.cancel') }}</a></div></form></section>
@endsection
