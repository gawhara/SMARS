<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale')
            ?: Auth::user()?->preferred_locale
            ?: config('app.locale');

        if (! in_array($locale, ['ar', 'en'], true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }
}
