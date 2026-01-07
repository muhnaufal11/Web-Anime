<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get locale from cookie first, then session, then config
        $locale = $request->cookie('app_locale') ?? 
                  Session::get('locale') ?? 
                  config('app.locale', 'id');
        
        // Ensure locale is valid
        $availableLocales = array_keys(config('app.available_locales', ['id' => [], 'en' => [], 'ja' => []]));
        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.locale', 'id');
        }

        // Set application locale
        App::setLocale($locale);
        
        // Share locale with views
        \View::share('appLocale', $locale);

        return $next($request);
    }
}
