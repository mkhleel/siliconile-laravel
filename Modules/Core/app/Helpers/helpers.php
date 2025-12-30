<?php

use Illuminate\Support\Collection;
use Modules\Cms\Models\Navigation;
use Modules\Core\Helpers\LanguageFlags;
use Modules\Core\Settings\GeneralSettings;

collect(glob(__DIR__.'/../../../*/app/Helpers/helpers.php'))->each(fn ($file) => require_once $file);

if (! function_exists('lang_flag')) {
    /**
     * Get SVG flag for a specific language code
     *
     * @param  string  $langCode  ISO language code
     * @return string SVG markup
     */
    function lang_flag(string $langCode): string
    {
        return LanguageFlags::getFlag($langCode);
    }
}

if (! function_exists('menu')) {
    function menu($key): Collection
    {
        $menu = Navigation::where('key', $key)->first();

        return $menu ? collect($menu->items) : collect([]);
    }
}

if (! function_exists('prism_ai')) {

    function prism_ai($message, $stream = false, $model = 'llama-3.3-70b-versatile')
    {
        $provider = app(GeneralSettings::class)->ai_provider;
        $model = app(GeneralSettings::class)->ai_model ?? $model;
        $apiKey = app(GeneralSettings::class)->ai_api_key;

        if (! $provider || ! $apiKey) {
            return 'AI provider or API key is not set.';
        }

        $response = prism()
            ->text()
            ->withSystemPrompt(config('core.ai.prompts.system')) // use view
            ->using($provider, $model)
            ->withPrompt($message);

        if ($stream) {
            return $response->asStream();
        } else {
            $response = $response->asText();

            return trim($response->text, '"');
        }

    }
}

if (! function_exists('generalSetting')) {

    function generalSetting($path = '')
    {

        $generalSettings = app(GeneralSettings::class);

        if ($path) {
            return $generalSettings->$path;
        }

        return $generalSettings;
    }
}

if (! function_exists('currentLocale')) {
    /**
     * Get the current application locale dynamically
     * Checks session, user preference, general settings, then config
     *
     * @return string Current locale code (e.g., 'en', 'ar', 'fr')
     */
    function currentLocale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('currentCurrency')) {
    /**
     * Get the current active currency code dynamically
     * Checks session for user preference, then falls back to site default
     *
     * @return string Currency ISO code (e.g., 'USD', 'SAR', 'EUR')
     */
    function currentCurrency(): string
    {
        // Check if user has a preferred currency in session (for guests)
        if (session()->has('preferred_currency')) {
            return session('preferred_currency');
        }
        
        // Fall back to site default currency
        return generalSetting('preferred_currency') ?? config('core.localization.preferred_currency', 'SAR');
    }
}

if (! function_exists('formatCurrency')) {
    /**
     * Format a price amount with the current currency
     * Converts from base currency to user's preferred currency
     *
     * @param  float  $amount  The amount in base currency (USD)
     * @param  string|null  $fromCurrency  Source currency (uses base if not provided)
     * @return \Illuminate\Support\HtmlString|string Formatted price with currency symbol
     */
    function formatCurrency(float $amount, ?string $fromCurrency = null): \Illuminate\Support\HtmlString|string
    {
        $currencyService = app(\Modules\Core\Services\CurrencyService::class);
        $fromCurrency = $fromCurrency ?? config('core.localization.preferred_currency', 'SAR');
        
        return $currencyService->formatPrice($amount, $fromCurrency);
    }
}

if (! function_exists('currencySymbol')) {
    /**
     * Get the symbol for the current or specified currency
     *
     * @param  string|null  $currencyCode  Optional currency code (uses current if not provided)
     * @return string Currency symbol (e.g., '$', '﷼', '€')
     */
    function currencySymbol(?string $currencyCode = null): string
    {
        $currency = $currencyCode ?? currentCurrency();
        $currencyModel = \Modules\Core\Models\Localization\Currency::where('iso', $currency)->first();
        
        return $currencyModel?->symbol ?? $currency;
    }
}

if (! function_exists('get_avatar')) {
    /**
     * Get avatar URL for a user based on their email or name.
     *
     * @param  string|null  $email  User's email
     * @param  string|null  $name  User's name
     * @param  int  $size  Size of the avatar in pixels
     * @return string Avatar URL
     */
    function get_avatar(?string $email = null, ?string $name = null, $letter = false, int $size = 80): string
    {
        if ($email) {
            $hash = md5(strtolower(trim($email)));

            return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";

            // Use Gravatar if email is provided
        } elseif ($name) {

            if ($letter) {
                // Generate initial-based avatar if name is provided
                $initials = strtoupper(substr($name, 0, 2));
                $backgroundColor = substr(md5($name), 0, 6); // Generate a color based on the name

                // Generate SVG
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'">';
                $svg .= '<rect width="100%" height="100%" fill="#'.$backgroundColor.'"/>';
                $svg .= '<text x="50%" y="50%" dy=".1em" fill="#ffffff" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-size="'.($size / 2).'">'.$initials.'</text>';
                $svg .= '</svg>';

                return 'data:image/svg+xml;base64,'.base64_encode($svg);
            }

            return 'https://api.dicebear.com/9.x/glass/svg?seed='.urlencode($name);

        }

        // Default avatar if neither email nor name is provided
        return 'https://api.dicebear.com/9.x/glass/svg?seed='.urlencode($name);
    }
}

if (! function_exists('uploadToCloudflareStream')) {
    /**
     * @throws ConnectionException
     */
    function uploadToCloudflareStream($filePath)
    {
        $apiToken = config('services.cloudflare.api_token');
        $accountId = config('services.cloudflare.account_id');
        $response = Http::withToken($apiToken)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post("https://api.cloudflare.com/client/v4/accounts/{$accountId}/stream");

        return $response->json();
    }
}
if (! function_exists('getEmbed')) {

    function getEmbed($url): string
    {
        if (strpos($url, 'youtube') !== false) {
            $url = str_replace('watch?v=', 'embed/', $url);
            $url = str_replace('https://www.youtube.com', 'https://www.youtube-nocookie.com', $url);

            return '<iframe width="100%" height="400" src="'.$url.'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        } elseif (strpos($url, 'vimeo') !== false) {
            $url = str_replace('vimeo.com', 'player.vimeo.com/video', $url);

            return '<iframe width="100%" height="100%" src="'.$url.'" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
        } else {
            return '';
        }
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        $languages = ['ar', 'he', 'ur', 'fa', 'ps', 'sd', 'ku', 'ug', 'ckb'];
        if (in_array(app()->getLocale(), $languages)) {
            return true;
        }

        return false;
    }
}

if (! function_exists('importCsv')) {
    function importCsv($file): array
    {
        $header = null;
        $csv = [];
        if (($handle = fopen($file, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (! $header) {
                    $header = $data;
                } else {
                    $csv[] = array_combine($header, $data);
                }
            }
            fclose($handle);
        }

        return $csv;
    }
}

if (! function_exists('hexToRgb')) {
    function hexToRgb($hexColor = '#dddddd')
    {
        // Ensure we have a default color and remove '#' if present
        $hexColor = empty($hexColor) ? '#dddddd' : $hexColor;
        $hexColor = ltrim($hexColor, '#');

        // Validate hex color format (3 or 6 characters)
        if (! preg_match('/^[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $hexColor)) {
            return [221, 221, 221]; // Return default color RGB values if invalid
        }

        // Convert 3-digit hex to 6-digit
        if (strlen($hexColor) === 3) {
            $hexColor = $hexColor[0].$hexColor[0].$hexColor[1].$hexColor[1].$hexColor[2].$hexColor[2];
        }

        // Split into RGB pairs and convert to decimal
        try {
            $rgb = array_map('hexdec', str_split($hexColor, 2));
            [$iRed, $iGreen, $iBlue] = $rgb;

            return [$iRed, $iGreen, $iBlue];
        } catch (Exception $e) {
            return [221, 221, 221]; // Return default color RGB values if conversion fails
        }
    }
}
