@extends('layouts.app')

@section('title', $company->localizedName())

@php
    $legalName = app()->getLocale() === 'ar' ? $company->legal_name_ar : $company->legal_name_en;
@endphp

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">{{ __('app.company_info.profile') }}</span>
            <h1>{{ $company->localizedName() }}</h1>
            <p>{{ $legalName ?: $company->name_en }}</p>
        </div>
        <div class="table-actions">
            <a class="ghost-button" href="{{ route('companies.edit', $company) }}">{{ __('app.edit') }}</a>
            <a class="ghost-button" href="{{ route('companies.index') }}">{{ __('app.cancel') }}</a>
        </div>
    </section>

    @include('partials.flash')

    <section class="panel profile-header">
        @include('partials.company-mark', ['company' => $company, 'class' => 'company-mark-lg'])
        <div class="profile-badges">
            <span class="status-badge {{ $company->is_active ? 'success' : 'muted' }}">{{ $company->is_active ? __('app.active') : __('app.inactive') }}</span>
            <span class="status-badge muted">{{ __('app.code') }}: {{ $company->code }}</span>
            <span class="status-badge info">{{ __('app.branches_count') }}: {{ $company->branches_count }}</span>
        </div>
    </section>

    <div class="detail-columns">
        <section class="panel">
            <div class="panel-header"><h2>{{ __('app.company_info.legal_information') }}</h2></div>
            <div class="panel-body">
                <dl class="detail-list">
                    <div><dt>{{ __('app.name_ar') }}</dt><dd>{{ $company->name_ar }}</dd></div>
                    <div><dt>{{ __('app.name_en') }}</dt><dd>{{ $company->name_en }}</dd></div>
                    <div><dt>{{ __('app.company_info.legal_name_ar') }}</dt><dd>{{ $company->legal_name_ar ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company_info.legal_name_en') }}</dt><dd>{{ $company->legal_name_en ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company_info.cr_number') }}</dt><dd>{{ $company->cr_number ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company_info.vat_number') }}</dt><dd>{{ $company->vat_number ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company_info.established_date') }}</dt><dd>{{ optional($company->established_date)->format('d/m/Y') ?: __('app.none') }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h2>{{ __('app.company_info.contact_details') }}</h2></div>
            <div class="panel-body">
                <dl class="detail-list">
                    <div><dt>{{ __('app.company_info.phone') }}</dt><dd>{{ $company->phone ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company_info.email') }}</dt><dd>{{ $company->email ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company_info.website') }}</dt><dd>{{ $company->website ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company_info.city') }}</dt><dd>{{ $company->city ?: __('app.none') }}</dd></div>
                    <div><dt>{{ __('app.company_info.address') }}</dt><dd>{{ $company->address ?: __('app.none') }}</dd></div>
                </dl>
            </div>
        </section>
    </div>

    <section class="panel">
        <div class="panel-header">
            <h2>{{ __('app.company_info.branches') }}</h2>
            <a class="ghost-button" href="{{ route('branches.index', ['company_id' => $company->id]) }}">{{ __('app.view') }}</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.name_ar') }}</th>
                        <th>{{ __('app.name_en') }}</th>
                        <th>{{ __('app.location') }}</th>
                        <th>{{ __('app.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($company->branches as $branch)
                        <tr>
                            <td>{{ $branch->name_ar }}</td>
                            <td>{{ $branch->name_en }}</td>
                            <td>{{ $branch->location ?: __('app.none') }}</td>
                            <td><span class="status-badge {{ $branch->is_active ? 'success' : 'muted' }}">{{ $branch->is_active ? __('app.active') : __('app.inactive') }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">{{ __('app.none') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
