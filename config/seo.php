<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Information
    |--------------------------------------------------------------------------
    */

    'site_name' => env('SEO_SITE_NAME', 'Siliconile'),
    'site_title' => env('SEO_SITE_TITLE', 'Siliconile | Empowering Egypt\'s Next Generation of Tech Startups'),
    'site_description' => env('SEO_SITE_DESCRIPTION', 'Leading startup incubator in Luxor, Egypt. We provide coworking spaces, funding, technical support, mentorship, and workshops to help tech startups grow and succeed.'),
    'site_keywords' => env('SEO_SITE_KEYWORDS', 'startup incubator, Egypt, Luxor, tech startups, coworking, funding, mentorship, innovation hub, entrepreneurship'),

    /*
    |--------------------------------------------------------------------------
    | Social & Contact Information
    |--------------------------------------------------------------------------
    */

    'company_name' => env('SEO_COMPANY_NAME', 'Siliconile'),
    'contact_email' => env('SEO_CONTACT_EMAIL', 'info@siliconile.com'),
    'contact_phone' => env('SEO_CONTACT_PHONE', ''),

    /*
    |--------------------------------------------------------------------------
    | Social Media Profiles
    |--------------------------------------------------------------------------
    */

    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK', ''),
        'twitter' => env('SOCIAL_TWITTER', ''),
        'instagram' => env('SOCIAL_INSTAGRAM', ''),
        'linkedin' => env('SOCIAL_LINKEDIN', ''),
        'youtube' => env('SOCIAL_YOUTUBE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default SEO Images
    |--------------------------------------------------------------------------
    */

    'default_image' => env('SEO_DEFAULT_IMAGE', '/images/1.png'),
    'default_image_width' => env('SEO_DEFAULT_IMAGE_WIDTH', 1200),
    'default_image_height' => env('SEO_DEFAULT_IMAGE_HEIGHT', 630),
    'twitter_card_type' => env('SEO_TWITTER_CARD_TYPE', 'summary_large_image'),

    /*
    |--------------------------------------------------------------------------
    | Organization Schema
    |--------------------------------------------------------------------------
    */

    'organization' => [
        'name' => env('SEO_ORG_NAME', config('app.name')),
        'logo' => env('SEO_ORG_LOGO', '/images/1.png'),
        'address' => [
            'streetAddress' => env('SEO_ORG_STREET', ''),
            'addressLocality' => env('SEO_ORG_CITY', ''),
            'addressRegion' => env('SEO_ORG_REGION', ''),
            'postalCode' => env('SEO_ORG_POSTAL', ''),
            'addressCountry' => env('SEO_ORG_COUNTRY', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots & Indexing
    |--------------------------------------------------------------------------
    */

    'robots' => [
        'default' => env('SEO_ROBOTS_DEFAULT', 'index, follow'),
        'noindex_environments' => ['local', 'staging'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap Configuration
    |--------------------------------------------------------------------------
    */

    'sitemap' => [
        'enabled' => env('SEO_SITEMAP_ENABLED', true),
        'cache_duration' => env('SEO_SITEMAP_CACHE_DURATION', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Codes
    |--------------------------------------------------------------------------
    */

    'verification' => [
        'google' => env('GOOGLE_SITE_VERIFICATION', ''),
        'bing' => env('BING_SITE_VERIFICATION', ''),
        'yandex' => env('YANDEX_SITE_VERIFICATION', ''),
        'pinterest' => env('PINTEREST_SITE_VERIFICATION', ''),
    ],

];
