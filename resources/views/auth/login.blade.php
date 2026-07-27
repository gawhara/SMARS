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
    <title>{{ __('app.login') }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-page">
        <section class="auth-hero">
            <div class="brand">
                <div class="brand-mark">S</div>
                <div>
                    <strong>{{ __('app.app_name') }}</strong>
                </div>
            </div>
            <div>
                <span class="eyebrow">{{ __('app.stitch_aligned') }}</span>
                <h1>{{ __('app.foundation_ready') }}</h1>
                <p>{{ __('app.foundation_note') }}</p>
            </div>
            <div class="auth-hero-grid">
                <span>{{ __('app.offline_ready') }}</span>
                <span>{{ __('app.local_font') }}</span>
                <span>{{ __('app.database_ready') }}</span>
            </div>
        </section>

        <section class="auth-panel">
            <div class="brand auth-brand">
                <div class="brand-mark">S</div>
                <div>
                    <strong>{{ __('app.app_name') }}</strong>
                    <span>{{ __('app.foundation') }}</span>
                </div>
            </div>

            <div>
                <h1>{{ __('app.welcome_back') }}</h1>
                <p>{{ __('app.login_intro') }}</p>
            </div>

            <form class="auth-form" method="POST" action="{{ route('login.store') }}">
                @csrf

                <label>
                    <span>{{ __('app.email') }}</span>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>{{ __('app.password') }}</span>
                    <input type="password" name="password" required>
                    @error('password')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label class="check-row">
                    <input type="checkbox" name="remember">
                    <span>{{ __('app.remember_me') }}</span>
                </label>

                <button class="primary-button" type="submit">{{ __('app.login') }}</button>
            </form>

            <div class="auth-languages">
                <a class="{{ $locale === 'ar' ? 'active' : '' }}" href="{{ route('language.switch', 'ar') }}">{{ __('app.arabic') }}</a>
                <a class="{{ $locale === 'en' ? 'active' : '' }}" href="{{ route('language.switch', 'en') }}">{{ __('app.english') }}</a>
            </div>
        </section>
    </main>
</body>
</html>
