@php $p = $policy; @endphp
<div class="latency-fields">
    <label class="att-field">
        <span>{{ __('app.latency.name') }}</span>
        <input type="text" name="name" value="{{ old('name', $p?->name) }}" required maxlength="120">
    </label>
    <label class="att-field">
        <span>{{ __('app.latency.grace_minutes') }}</span>
        <input type="number" name="grace_minutes" min="0" max="240" value="{{ old('grace_minutes', $p?->grace_minutes ?? 9) }}" required>
        <small class="field-hint">{{ __('app.latency.grace_hint') }}</small>
    </label>
    <label class="att-field">
        <span>{{ __('app.latency.multiplier') }}</span>
        <input type="number" name="multiplier" step="0.25" min="0" max="10" value="{{ old('multiplier', $p ? rtrim(rtrim(number_format((float) $p->multiplier, 2), '0'), '.') : '2') }}" required>
    </label>
    <label class="checkbox-field">
        <input type="checkbox" name="round_up_to_hour" value="1" @checked(old('round_up_to_hour', $p?->round_up_to_hour ?? true))>
        <span>{{ __('app.latency.round_up') }}</span>
        <small class="field-hint">{{ __('app.latency.round_up_hint') }}</small>
    </label>
    <label class="checkbox-field">
        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $p?->is_default ?? false))>
        <span>{{ __('app.latency.is_default') }}</span>
    </label>
    <label class="checkbox-field">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p?->is_active ?? true))>
        <span>{{ __('app.active') }}</span>
    </label>
</div>
