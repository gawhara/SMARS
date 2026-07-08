<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        validator(['locale' => $locale], [
            'locale' => ['required', Rule::in(['ar', 'en'])],
        ])->validate();

        session(['locale' => $locale]);

        if (Auth::check()) {
            Auth::user()->forceFill(['preferred_locale' => $locale])->save();
        }

        return back();
    }
}
