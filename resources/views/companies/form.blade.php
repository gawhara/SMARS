@extends('layouts.app')

@section('title', $company->exists ? __('app.edit') : __('app.add_new'))

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.organization_structure') }}</span>
            <h1>{{ $company->exists ? __('app.edit') : __('app.add_new') }} - {{ __('app.companies') }}</h1>
            <p>{{ __('app.organization_note') }}</p>
        </div>
    </section>

    <section class="panel form-panel">
        <form method="POST" action="{{ $company->exists ? route('companies.update', $company) : route('companies.store') }}">
            @csrf
            @if ($company->exists)
                @method('PUT')
            @endif

            <h3 class="form-section-title">{{ __('app.company_info.overview') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.name_ar') }} *</span>
                    <input name="name_ar" value="{{ old('name_ar', $company->name_ar) }}" required>
                    @error('name_ar')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.name_en') }} *</span>
                    <input name="name_en" value="{{ old('name_en', $company->name_en) }}" required>
                    @error('name_en')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.code') }} *</span>
                    <input name="code" value="{{ old('code', $company->code) }}" required>
                    @error('code')<small>{{ $message }}</small>@enderror
                </label>
                <label class="check-row">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $company->is_active ?? true))>
                    <span>{{ __('app.active') }}</span>
                </label>
            </div>

            <h3 class="form-section-title">{{ __('app.company_info.legal_information') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.company_info.legal_name_ar') }}</span>
                    <input name="legal_name_ar" value="{{ old('legal_name_ar', $company->legal_name_ar) }}">
                    @error('legal_name_ar')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.company_info.legal_name_en') }}</span>
                    <input name="legal_name_en" value="{{ old('legal_name_en', $company->legal_name_en) }}">
                    @error('legal_name_en')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.company_info.cr_number') }}</span>
                    <input name="cr_number" value="{{ old('cr_number', $company->cr_number) }}" inputmode="numeric" maxlength="10" placeholder="1010XXXXXX">
                    @error('cr_number')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.company_info.vat_number') }}</span>
                    <input name="vat_number" value="{{ old('vat_number', $company->vat_number) }}" inputmode="numeric" maxlength="15" placeholder="3XXXXXXXXXXXXXX">
                    @error('vat_number')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.company_info.established_date') }}</span>
                    <input type="date" name="established_date" max="{{ now()->toDateString() }}" value="{{ old('established_date', optional($company->established_date)->format('Y-m-d')) }}">
                    @error('established_date')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            <h3 class="form-section-title">{{ __('app.company_info.contact_details') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.company_info.phone') }}</span>
                    <input name="phone" value="{{ old('phone', $company->phone) }}">
                    @error('phone')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.company_info.email') }}</span>
                    <input type="text" name="email" value="{{ old('email', $company->email) }}" placeholder="info@example.com">
                    @error('email')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.company_info.website') }}</span>
                    <input name="website" value="{{ old('website', $company->website) }}" placeholder="https://example.com">
                    @error('website')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.company_info.city') }}</span>
                    <input name="city" value="{{ old('city', $company->city) }}">
                    @error('city')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.company_info.address') }}</span>
                    <input name="address" value="{{ old('address', $company->address) }}">
                    @error('address')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            <h3 class="form-section-title">{{ __('app.wps.section') }}</h3>
            <div class="form-grid">
                <label>
                    <span>{{ __('app.wps.establishment_id') }}</span>
                    <input name="wps_establishment_id" value="{{ old('wps_establishment_id', $company->wps_establishment_id) }}" placeholder="1-XXXXXXX">
                    @error('wps_establishment_id')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.wps.employer_bank_code') }}</span>
                    <input name="employer_bank_code" value="{{ old('employer_bank_code', $company->employer_bank_code) }}" maxlength="4" placeholder="80">
                    @error('employer_bank_code')<small>{{ $message }}</small>@enderror
                </label>
                <label>
                    <span>{{ __('app.wps.employer_iban') }}</span>
                    <input name="employer_iban" value="{{ old('employer_iban', $company->employer_iban) }}" maxlength="34" placeholder="SA...">
                    @error('employer_iban')<small>{{ $message }}</small>@enderror
                </label>
            </div>

            <div class="form-actions">
                <button class="primary-button" type="submit">{{ __('app.save') }}</button>
                <a class="ghost-button" href="{{ route('companies.index') }}">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
