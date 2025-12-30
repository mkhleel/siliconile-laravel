<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $availableLanguages = generalSetting()->site_languages ?? config('core.localization.languages', ['en']);
        
        if ($request->has('locale') && in_array($request->get('locale'), $availableLanguages)) {
            $locale = $request->get('locale');
            Session::put('locale', $locale);
        }

        $locale = Session::get('locale');
        if (! $locale) {
            $locale = generalSetting()->site_locale ?? config('app.locale');
        }
        
        // Ensure the selected locale is available
        if (! in_array($locale, $availableLanguages)) {
            $locale = generalSetting()->site_locale ?? config('app.locale');
            Session::put('locale', $locale);
        }
        
        app()->setLocale($locale);

        return $next($request);
    }
}
