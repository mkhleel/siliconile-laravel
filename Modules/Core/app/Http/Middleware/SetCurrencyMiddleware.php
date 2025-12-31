<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Modules\Core\Models\Localization\Currency;

class SetCurrencyMiddleware
{
    /**
     * Handle an incoming request to set the user's preferred currency.
     *
     * This middleware allows guests to select their preferred currency,
     * which is stored in the session and used throughout their browsing session.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get available currencies from database
        $availableCurrencies = Currency::where('is_activated', true)
            ->pluck('iso')
            ->toArray();

        // If currency is provided in request, validate and store it
        if ($request->has('currency') && in_array($request->get('currency'), $availableCurrencies)) {
            $currency = $request->get('currency');
            Session::put('preferred_currency', $currency);
        }

        // If no session currency is set, use the site default
        if (! Session::has('preferred_currency')) {
            $defaultCurrency = generalSetting()->preferred_currency ?? config('core.localization.preferred_currency', 'EGP');

            // Ensure the default currency is available
            if (in_array($defaultCurrency, $availableCurrencies)) {
                Session::put('preferred_currency', $defaultCurrency);
            } else {
                // Fallback to first available currency if default is not available
                Session::put('preferred_currency', $availableCurrencies[0] ?? 'USD');
            }
        }

        return $next($request);
    }
}
