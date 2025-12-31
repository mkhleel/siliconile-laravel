<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Payment module.
    |
    */

    // Default currency for payments
    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'EGP'),

    // Payment gateway configurations
    'gateways' => [

        // 2Checkout configuration
        '2checkout' => [
            'countries' => ['US', 'UK', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES'],
            'api_url' => env('TWOCHECKOUT_API_URL', 'https://api.2checkout.com/rest/6.0/'),
            'enabled' => env('TWOCHECKOUT_ENABLED', true),
            'sandbox' => env('TWOCHECKOUT_SANDBOX', true),
            'seller_id' => env('TWOCHECKOUT_SELLER_ID', ''),
            'secret_key' => env('TWOCHECKOUT_SECRET_KEY', ''),
            'private_key' => env('TWOCHECKOUT_PRIVATE_KEY', ''),
            'publishable_key' => env('TWOCHECKOUT_PUBLISHABLE_KEY', ''),
            'buy_link_secret_word' => env('TWOCHECKOUT_BUY_LINK_SECRET_WORD', 'tango'),
            'icon' => '/images/payment/2checkout.png',
            'scripts' => [
                'https://2pay-js.2checkout.com/v1/2pay.js',
            ],
        ],
        // Paytabs configuration
        'paytabs' => [
            // 'countries' => ['SA', 'EG'],
            'default_currency' => 'EGP',
            'enabled' => env('PAYTABS_ENABLED', true),
            'profile_id' => env('PAYTABS_PROFILE_ID', '136342'),
            'server_key' => env('PAYTABS_SERVER_KEY', 'SGJ99RDNJJ-JHWMJBW62T-DN6LD9KZM9'),
            'base_url' => env('PAYTABS_BASE_URL', 'https://secure-egypt.paytabs.com'),
            'checkout_lang' => env('PAYTABS_CHECKOUT_LANG', 'en'),
            'icon' => '/images/payment/paytabs.png',
        ],

        // Kashier.io configuration (Egyptian Payment Gateway)
        // @see https://developers.kashier.io/
        'kashier' => [
            'countries' => ['EG'],
            'enabled' => env('KASHIER_ENABLED', false),
            'test_mode' => env('KASHIER_TEST_MODE', true),
            // Merchant ID from Kashier Dashboard
            'merchant_id' => env('KASHIER_MERCHANT_ID', ''),
            // Secret Key for API authentication (Authorization header)
            'secret_key' => env('KASHIER_SECRET_KEY', ''),
            // Payment API Key for hash generation and webhook signature verification
            'api_key' => env('KASHIER_API_KEY', ''),
            // Default currency (EGP for Egyptian Pounds)
            'default_currency' => env('KASHIER_DEFAULT_CURRENCY', 'EGP'),
            // Display language for payment UI (en or ar)
            'display_lang' => env('KASHIER_DISPLAY_LANG', 'en'),
            // Allowed payment methods: card, wallet, bank_installments, fawry
            'allowed_methods' => env('KASHIER_ALLOWED_METHODS', 'card,wallet'),
            // Brand color for payment UI (hex or rgba)
            'brand_color' => env('KASHIER_BRAND_COLOR', null),
            // Iframe background color
            'iframe_bg_color' => env('KASHIER_IFRAME_BG_COLOR', '#FFFFFF'),
            'icon' => '/images/payment/kashier.png',
        ],

        'urway' => [
            'countries' => ['SA', 'AE', 'KW', 'BH', 'OM', 'QA', 'EG'],
            'enabled' => env('URWAY_ENABLED', false),
            'terminal_id' => env('URWAY_TERMINAL_ID', ''),
            'password' => env('URWAY_PASSWORD', ''),
            'secret_key' => env('URWAY_SECRET_KEY', ''),
            'icon' => '/images/payment/urway.png',
        ],

    ],

    // Payment logging configuration
    'logging' => [
        'channel' => env('PAYMENT_LOG_CHANNEL', 'stack'),
    ],
];
