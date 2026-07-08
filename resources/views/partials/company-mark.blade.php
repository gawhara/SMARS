{{-- Company logo tile: shows the brand logo when available, else the code initials. --}}
@php $logo = $company->logoUrl(); @endphp
@if ($logo)
    <span class="company-mark company-mark-logo {{ $class ?? '' }}">
        <img src="{{ $logo }}" alt="{{ $company->localizedName() }}">
    </span>
@else
    <span class="company-mark {{ $class ?? '' }}">{{ mb_strtoupper(mb_substr($company->code, 0, 2)) }}</span>
@endif
