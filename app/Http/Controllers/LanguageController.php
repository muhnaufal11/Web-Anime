<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class LanguageController extends Controller
{
    /**
     * Switch application locale
     */
    public function switch(Request $request, $locale)
    {
        // Validate locale
        $availableLocales = ['id', 'en', 'ja'];
        
        if (!in_array($locale, $availableLocales)) {
            return redirect()->back();
        }

        // Also store in session
        Session::put('locale', $locale);
        Session::save();

        // Create cookie and attach to response
        $cookie = Cookie::make('app_locale', $locale, 60 * 24 * 365, '/', null, false, false);
        
        // Get previous URL or home
        $previousUrl = url()->previous() ?: route('home');
        
        // Redirect with cookie
        return redirect($previousUrl)->withCookie($cookie);
    }
}
