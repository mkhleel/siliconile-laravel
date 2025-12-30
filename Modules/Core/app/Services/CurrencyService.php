<?php

namespace Modules\Core\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Log;
use Modules\Core\Models\Localization\Currency;

readonly class CurrencyService
{
    public function formatPrice(float $amount, string $fromCurrency = 'USD'): HtmlString|string
    {
        $fromCurrency = generalSetting()->currency ?? config('core.localization.base_currency') ?? $fromCurrency;
        
        // Use currentCurrency() helper which checks session for user preference first
        $targetCurrency = currentCurrency();
        
        $convertedAmount = $this->convertCurrency(
            $amount,
            $fromCurrency,
            $targetCurrency
        );

        return $this->format($convertedAmount, $targetCurrency);
    }

    public function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rates = self::getExchangeRates($fromCurrency);

        if (! $rates || ! isset($rates[$toCurrency])) {
            return null;
        }

        return ($amount / $rates[$toCurrency]['crate']);
    }

    /**
     * Format an amount with currency symbol
     *
     * @param  float  $amount  The amount to format
     * @param  string  $currency  The currency ISO code
     * @return HtmlString|string Formatted price with currency symbol
     */
    public function format(float $amount, string $currency): HtmlString|string
    {
        $format = Currency::where('iso', $currency)->first();
        
        if ($currency === 'SAR') {
            $format->symbol = '<svg class="inline-block w-5 h-5" id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1124.14 1256.39">
                        <defs>
                            <style>
                            .cls-1 {
                                fill: currentColor;
                            }
                            </style>
                        </defs>
                        <path class="cls-1" d="M699.62,1113.02h0c-20.06,44.48-33.32,92.75-38.4,143.37l424.51-90.24c20.06-44.47,33.31-92.75,38.4-143.37l-424.51,90.24Z"/>
                        <path class="cls-1" d="M1085.73,895.8c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.33v-135.2l292.27-62.11c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.27V66.13c-50.67,28.45-95.67,66.32-132.25,110.99v403.35l-132.25,28.11V0c-50.67,28.44-95.67,66.32-132.25,110.99v525.69l-295.91,62.88c-20.06,44.47-33.33,92.75-38.42,143.37l334.33-71.05v170.26l-358.3,76.14c-20.06,44.47-33.32,92.75-38.4,143.37l375.04-79.7c30.53-6.35,56.77-24.4,73.83-49.24l68.78-101.97v-.02c7.14-10.55,11.3-23.27,11.3-36.97v-149.98l132.25-28.11v270.4l424.53-90.28Z"/>
                        </svg>';
        }


        if (!$format) {
            // Fallback if currency not found
            return number_format($amount, 2) . ' ' . $currency;
        }

        $formatted = number_format($amount, 2);

        $result = $format->position === 'before'
            ? "{$format->symbol}{$formatted}"
            : "{$formatted} {$format->symbol}";

        return new HtmlString($result);
    }

    /**
     * Get the current active currency (respects user preference)
     *
     * @return string Currency ISO code
     */
    public function getCurrentCurrency(): string
    {
        return currentCurrency();
    }

    /**
     * Get the symbol for a specific currency
     *
     * @param  string|null  $currency  Currency ISO code (uses current if null)
     * @return string Currency symbol
     */
    public function getSymbol(?string $currency = null): string
    {
        $currency = $currency ?? $this->getCurrentCurrency();
        $currencyModel = Currency::where('iso', $currency)->first();
        
        return $currencyModel?->symbol ?? $currency;
    }

    private static function getExchangeRates(string $baseCurrency = 'USD', int $cacheDuration = 60): ?array
    {
        // Create a unique cache key
        $cacheKey = "2checkout_exchange_rates_{$baseCurrency}";

        // Check if rates are cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Fetch exchange rates from 2Checkout
            $response = Http::get('https://secure.2checkout.com/content/exchange-json.php', [
                'CURRENCY' => $baseCurrency,
            ]);

            // Check if request was successful
            if ($response->successful()) {
                $rates = $response->json();

                // Cache the rates
                Cache::put($cacheKey, $rates, now()->addMinutes($cacheDuration));

                return $rates;
            }

            // Log error if request fails
            Log::error('2Checkout Exchange Rate Fetch Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            // Log any exceptions
            Log::error('2Checkout Exchange Rate Fetch Error', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getExchangeRate(string $fromCurrency, string $toCurrency): ?float
    {
        $rates = self::getExchangeRates($fromCurrency);

        return $rates[$toCurrency]['crate'] ?? null;
    }
}
