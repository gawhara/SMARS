@php
    $rawValue = (string) $value;
    $time = $rawValue !== '' ? \Carbon\Carbon::createFromFormat('H:i', substr($rawValue, 0, 5)) : null;
    $hour = $time?->format('h') ?? '08';
    $minute = $time?->format('i') ?? '00';
    $period = $time?->format('A') ?? 'AM';
@endphp

<label>
    <span>{{ $label }}</span>
    <div class="time-12-control" data-time-12>
        <select data-time-hour aria-label="{{ __('app.hour') }}">
            @for ($option = 1; $option <= 12; $option++)
                <option value="{{ str_pad($option, 2, '0', STR_PAD_LEFT) }}" @selected((int) $hour === $option)>{{ str_pad($option, 2, '0', STR_PAD_LEFT) }}</option>
            @endfor
        </select>
        <span class="time-separator">:</span>
        <input type="number" min="0" max="59" step="1" value="{{ $minute }}" data-time-minute aria-label="{{ __('app.minute') }}">
        <select data-time-period aria-label="{{ __('app.time_period') }}">
            <option value="AM" @selected($period === 'AM')>{{ __('app.time_am') }}</option>
            <option value="PM" @selected($period === 'PM')>{{ __('app.time_pm') }}</option>
        </select>
        <input type="hidden" name="{{ $name }}" value="{{ $rawValue }}" data-time-value @if($required ?? false) required @endif>
    </div>
    @error($name)<small>{{ $message }}</small>@enderror
</label>
