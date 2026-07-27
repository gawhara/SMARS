@extends('layouts.print')

@section('title', __('app.att.monthly_report_title').' — '.$employee->localizedName())

@section('content')
    <div class="print-toolbar no-print">
        <button class="pt-print" type="button" onclick="window.print()">{{ __('app.att.print') }}</button>
        <a class="pt-back" href="{{ route('attendance.employee', $employee) }}">{{ __('app.att.back_to_directory') }}</a>
    </div>

    <div class="print-watermark" aria-hidden="true"><span>{{ $employee->localizedName() }}</span></div>

    <div class="print-sheet">
        <div class="print-head">
            <div>
                <div class="company">{{ $employee->company?->localizedName() ?? config('app.name') }}</div>
                <div class="muted">{{ __('app.att.monthly_report_title') }}</div>
            </div>
            <div style="text-align:end">
                <h1><bdi dir="ltr">{{ $from->format('Y-m-d') }} → {{ $to->format('Y-m-d') }}</bdi></h1>
                <div class="muted">{{ __('app.att.generated_at') }}: <bdi dir="ltr">{{ now()->format('Y-m-d H:i') }}</bdi></div>
            </div>
        </div>

        <div class="print-meta">
            <div><span>{{ __('app.att.employee') }}</span><strong>{{ $employee->localizedName() }}</strong></div>
            <div><span>{{ __('app.enroll.device_user_id') }}</span><strong><bdi dir="ltr">{{ $employee->hr_employee_id ?: $employee->employee_code }}</bdi></strong></div>
            <div><span>{{ __('app.department') }}</span><strong>{{ $employee->department?->localizedName() ?? '—' }}</strong></div>
        </div>

        <div class="print-summary">
            <div class="box"><span>{{ __('app.att.st_present') }}</span><strong class="st-present">{{ $summary['present'] }}</strong></div>
            <div class="box"><span>{{ __('app.att.st_late') }}</span><strong class="st-late">{{ $summary['late'] }}</strong></div>
            <div class="box"><span>{{ __('app.att.st_absent') }}</span><strong class="st-absent">{{ $summary['absent'] }}</strong></div>
            <div class="box"><span>{{ __('app.att.st_leave') }}</span><strong class="st-leave">{{ $summary['leave'] }}</strong></div>
            <div class="box"><span>{{ __('app.att.st_holiday') }}</span><strong>{{ $summary['holiday'] }}</strong></div>
            <div class="box"><span>{{ __('app.att.st_rest') }}</span><strong>{{ $summary['rest'] }}</strong></div>
            <div class="box"><span>{{ __('app.att.worked_hours') }}</span><strong>{{ $workedHours }}</strong></div>
            <div class="box"><span>{{ __('app.att.attendance_rate') }}</span><strong>{{ $rate }}%</strong></div>
        </div>

        <table class="print-days">
            <thead>
                <tr>
                    <th>{{ __('app.att.date') }}</th>
                    <th>{{ __('app.att.weekday') }}</th>
                    <th>{{ __('app.att.first_in') }}</th>
                    <th>{{ __('app.att.last_out') }}</th>
                    <th>{{ __('app.att.worked_hours') }}</th>
                    <th>{{ __('app.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php $s = $row['summary']; $status = $row['status']; @endphp
                    <tr class="{{ in_array($status, ['rest', 'holiday']) ? 'row-weekend' : '' }}">
                        <td><bdi dir="ltr">{{ $row['date']->format('Y-m-d') }}</bdi></td>
                        <td>{{ $row['date']->locale(app()->getLocale())->dayName }}</td>
                        <td><bdi dir="ltr">{{ $s ? $s->localizedTime('first_in_at') : '—' }}</bdi></td>
                        <td><bdi dir="ltr">{{ $s ? $s->localizedTime('last_out_at') : '—' }}</bdi></td>
                        <td>{{ $s ? number_format($s->worked_minutes / 60, 2) : '—' }}</td>
                        <td class="st st-{{ $status }}">{{ $status === 'future' ? '—' : __('app.att.st_'.$status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="print-sign">
            <div>{{ __('app.att.signature_employee') }}</div>
            <div>{{ __('app.att.signature_manager') }}</div>
        </div>
        <div class="print-foot">{{ config('app.name') }} · {{ __('app.att.monthly_report_title') }}</div>
    </div>
@endsection
