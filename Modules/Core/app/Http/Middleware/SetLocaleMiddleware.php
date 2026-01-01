<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $supportedLocales = generalSetting()->site_languages ?? config('core.localization.languages', ['en', 'ar']);
        
        if ($request->has('locale') && in_array($request->get('locale'), $supportedLocales)) {
            $locale = $request->get('locale');
            Session::put('locale', $locale);
            // dd($locale);
            // Redirect to remove the query parameter
            return redirect()->to($request->url());
        }


        $locale = Session::get('locale', $request->cookie('locale', config('app.locale')));
        if (! $locale) {
            $locale = generalSetting()->site_locale ?? config('app.locale');
        }
        
        // Ensure the selected locale is available
        if (! in_array($locale, $supportedLocales)) {
            $locale = generalSetting()->site_locale ?? config('app.locale');
            Session::put('locale', $locale);
        }

        $locale = Session::get('locale', $request->cookie('locale', config('app.locale')));
        
        app()->setLocale($locale);

        $response = $next($request);

        // Store locale in cookie for persistence
        if ($response instanceof Response && method_exists($response, 'cookie')) {
            $response->cookie('locale', $locale, 60 * 24 * 365); // 1 year
        }

        return $response;
    }
}
