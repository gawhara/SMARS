{{--
    Compact company filter picker (used on Branches & Employees).
    Params: $companies, $route (name), $countField, $label
--}}
@php $active = (string) request('company_id'); @endphp

<div class="company-picker">
    <a class="picker-card {{ $active === '' ? 'active' : '' }}" href="{{ route($route) }}">
        <span class="picker-logo picker-logo-all">★</span>
        <span class="picker-body">
            <span class="picker-name">{{ __('app.all_companies') }}</span>
            <span class="picker-meta">{{ $companies->sum($countField) }} · {{ $label }}</span>
        </span>
    </a>

    @foreach ($companies as $company)
        @php $logo = $company->logoUrl(); @endphp
        <a class="picker-card {{ $active === (string) $company->id ? 'active' : '' }}"
           href="{{ route($route, ['company_id' => $company->id]) }}">
            @if ($logo)
                <span class="picker-logo has-img"><img src="{{ $logo }}" alt="{{ $company->localizedName() }}"></span>
            @else
                <span class="picker-logo">{{ mb_strtoupper(mb_substr($company->code, 0, 2)) }}</span>
            @endif
            <span class="picker-body">
                <span class="picker-name">{{ $company->localizedName() }}</span>
                <span class="picker-meta">{{ $company->{$countField} }} · {{ $company->code }}</span>
            </span>
        </a>
    @endforeach
</div>
