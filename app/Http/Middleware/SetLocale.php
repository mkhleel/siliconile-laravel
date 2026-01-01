<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', ['en', 'ar']);
        // Check for locale in query string (for switching)
        if ($request->has('lang') && in_array($request->query('lang'), $supportedLocales, true)) {
            $locale = $request->query('lang');
            Session::put('locale', $locale);
            
            // Redirect to remove the query parameter
            return redirect()->to($request->url());
        }

        // Get locale from session, cookie, or default
        $locale = Session::get('locale', $request->cookie('locale', config('app.locale')));

        // Validate locale
        if (! in_array($locale, $supportedLocales, true)) {
            $locale = config('app.locale');
        }

        // Set the application locale
        App::setLocale($locale);

        $response = $next($request);

        // Store locale in cookie for persistence
        if ($response instanceof Response && method_exists($response, 'cookie')) {
            $response->cookie('locale', $locale, 60 * 24 * 365); // 1 year
        }

        return $response;
    }
}
