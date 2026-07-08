@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.dashboard')) - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="smars-body">
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-mark">S</div>
                <div>
                    <strong>{{ __('app.app_name') }}</strong>
                    <span>{{ __('app.foundation_subtitle') }}</span>
                </div>
            </div>

            @include('partials.sidebar')
        </aside>

        <div class="content-shell">
            <header class="topbar">
                <button class="icon-button menu-toggle" type="button" data-sidebar-toggle aria-label="Menu">Menu</button>

                <div class="topbar-search">
                    <span>{{ __('app.search') }}</span>
                </div>

                <div class="topbar-actions">
                    <a class="language-link {{ $locale === 'ar' ? 'active' : '' }}" href="{{ route('language.switch', 'ar') }}">{{ __('app.arabic') }}</a>
                    <a class="language-link {{ $locale === 'en' ? 'active' : '' }}" href="{{ route('language.switch', 'en') }}">{{ __('app.english') }}</a>
                    <button class="icon-button" type="button" aria-label="{{ __('app.notifications') }}">N</button>
                    <div class="user-chip">
                        <span>{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                        <strong>{{ auth()->user()->name }}</strong>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="ghost-button" type="submit">{{ __('app.logout') }}</button>
                    </form>
                </div>
            </header>

            <main class="main-content">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
