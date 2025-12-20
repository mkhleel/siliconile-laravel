# Complete SEO Implementation Guide for Laravel Projects

This guide provides step-by-step instructions to implement comprehensive SEO functionality in any Laravel project. Follow these steps to add meta tags, Open Graph, Twitter Cards, schema.org structured data, and dynamic sitemaps.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Step 1: Create SEO Configuration](#step-1-create-seo-configuration)
3. [Step 2: Create SEO Service](#step-2-create-seo-service)
4. [Step 3: Create Sitemap Controller](#step-3-create-sitemap-controller)
5. [Step 4: Create Blade Components](#step-4-create-blade-components)
6. [Step 5: Create Helper Functions](#step-5-create-helper-functions)
7. [Step 6: Update Routes](#step-6-update-routes)
8. [Step 7: Update Head Template](#step-7-update-head-template)
9. [Step 8: Create Tests](#step-8-create-tests)
10. [Step 9: Configuration & Usage](#step-9-configuration--usage)
11. [Validation & Testing](#validation--testing)

---

## Prerequisites

- Laravel 11+ project
- PHP 8.2+
- Composer installed
- Basic understanding of Laravel concepts

---

## Step 1: Create SEO Configuration

**File:** `config/seo.php`

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Information
    |--------------------------------------------------------------------------
    */

    'site_name' => env('SEO_SITE_NAME', config('app.name')),
    'site_title' => env('SEO_SITE_TITLE', config('app.name')),
    'site_description' => env('SEO_SITE_DESCRIPTION', 'Your site description here.'),
    'site_keywords' => env('SEO_SITE_KEYWORDS', 'keywords, here'),
    
    /*
    |--------------------------------------------------------------------------
    | Social & Contact Information
    |--------------------------------------------------------------------------
    */

    'company_name' => env('SEO_COMPANY_NAME', config('app.name')),
    'contact_email' => env('SEO_CONTACT_EMAIL', 'info@' . parse_url(config('app.url'), PHP_URL_HOST)),
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

    'default_image' => env('SEO_DEFAULT_IMAGE', '/images/og-default.jpg'),
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
        'type' => env('SEO_ORG_TYPE', 'Organization'),
        'logo' => env('SEO_ORG_LOGO', '/images/logo.png'),
        'address' => [
            'street' => env('SEO_ORG_ADDRESS_STREET', ''),
            'city' => env('SEO_ORG_ADDRESS_CITY', ''),
            'region' => env('SEO_ORG_ADDRESS_REGION', ''),
            'postal_code' => env('SEO_ORG_ADDRESS_POSTAL', ''),
            'country' => env('SEO_ORG_ADDRESS_COUNTRY', ''),
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
```

---

## Step 2: Create SEO Service

**File:** `app/Services/SeoService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SeoService
{
    protected array $meta = [];
    protected array $openGraph = [];
    protected array $twitter = [];
    protected array $schema = [];

    public function __construct()
    {
        $this->setDefaults();
    }

    protected function setDefaults(): void
    {
        $this->meta = [
            'title' => config('seo.site_title'),
            'description' => config('seo.site_description'),
            'keywords' => config('seo.site_keywords'),
            'robots' => $this->getRobotsContent(),
            'canonical' => URL::current(),
        ];

        $this->openGraph = [
            'type' => 'website',
            'site_name' => config('seo.site_name'),
            'locale' => app()->getLocale(),
            'image' => $this->getAbsoluteUrl(config('seo.default_image')),
            'image:width' => config('seo.default_image_width'),
            'image:height' => config('seo.default_image_height'),
        ];

        $this->twitter = [
            'card' => config('seo.twitter_card_type'),
        ];
    }

    public function setTitle(string $title, bool $appendSiteName = true): self
    {
        $this->meta['title'] = $appendSiteName
            ? $title.' | '.config('seo.site_name')
            : $title;

        $this->openGraph['title'] = $title;
        $this->twitter['title'] = $title;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $description = Str::limit(strip_tags($description), 160);

        $this->meta['description'] = $description;
        $this->openGraph['description'] = $description;
        $this->twitter['description'] = $description;

        return $this;
    }

    public function setKeywords(string|array $keywords): self
    {
        $this->meta['keywords'] = is_array($keywords)
            ? implode(', ', $keywords)
            : $keywords;

        return $this;
    }

    public function setCanonical(string $url): self
    {
        $this->meta['canonical'] = $url;
        $this->openGraph['url'] = $url;

        return $this;
    }

    public function setImage(string $image, ?int $width = null, ?int $height = null): self
    {
        $absoluteUrl = $this->getAbsoluteUrl($image);

        $this->openGraph['image'] = $absoluteUrl;
        $this->twitter['image'] = $absoluteUrl;

        if ($width) {
            $this->openGraph['image:width'] = $width;
        }
        if ($height) {
            $this->openGraph['image:height'] = $height;
        }

        return $this;
    }

    public function setType(string $type): self
    {
        $this->openGraph['type'] = $type;

        return $this;
    }

    public function setRobots(string $robots): self
    {
        $this->meta['robots'] = $robots;

        return $this;
    }

    public function noIndex(): self
    {
        $this->meta['robots'] = 'noindex, nofollow';

        return $this;
    }

    public function addMeta(string $name, string $content): self
    {
        $this->meta[$name] = $content;

        return $this;
    }

    public function addOpenGraph(string $property, string $content): self
    {
        $this->openGraph[$property] = $content;

        return $this;
    }

    public function addTwitter(string $name, string $content): self
    {
        $this->twitter[$name] = $content;

        return $this;
    }

    public function addSchema(array $schema): self
    {
        $this->schema[] = $schema;

        return $this;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getOpenGraph(): array
    {
        return $this->openGraph;
    }

    public function getTwitter(): array
    {
        return $this->twitter;
    }

    public function getSchemas(): array
    {
        return $this->schema;
    }

    protected function getAbsoluteUrl(string $url): string
    {
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return rtrim(config('app.url'), '/').'/'.ltrim($url, '/');
    }

    protected function getRobotsContent(): string
    {
        $noindexEnvs = config('seo.robots.noindex_environments', []);

        if (in_array(app()->environment(), $noindexEnvs)) {
            return 'noindex, nofollow';
        }

        return config('seo.robots.default', 'index, follow');
    }

    public function getWebsiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('seo.site_name'),
            'url' => config('app.url'),
            'description' => config('seo.site_description'),
            'inLanguage' => app()->getLocale(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => config('app.url').'/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function getOrganizationSchema(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => config('seo.organization.type', 'Organization'),
            'name' => config('seo.organization.name'),
            'url' => config('app.url'),
            'logo' => $this->getAbsoluteUrl(config('seo.organization.logo')),
        ];

        if (config('seo.contact_email')) {
            $schema['email'] = config('seo.contact_email');
        }

        if (config('seo.contact_phone')) {
            $schema['telephone'] = config('seo.contact_phone');
        }

        $address = config('seo.organization.address');
        if (! empty(array_filter($address))) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['street'] ?? '',
                'addressLocality' => $address['city'] ?? '',
                'addressRegion' => $address['region'] ?? '',
                'postalCode' => $address['postal_code'] ?? '',
                'addressCountry' => $address['country'] ?? '',
            ];
        }

        $socialProfiles = array_filter(config('seo.social', []));
        if (! empty($socialProfiles)) {
            $schema['sameAs'] = array_values($socialProfiles);
        }

        return $schema;
    }

    public function getWebPageSchema(?string $title = null, ?string $description = null): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title ?? $this->meta['title'],
            'description' => $description ?? $this->meta['description'],
            'url' => $this->meta['canonical'] ?? URL::current(),
            'inLanguage' => app()->getLocale(),
            'isPartOf' => [
                '@type' => 'WebSite',
                'url' => config('app.url'),
            ],
        ];
    }
}
```

---

## Step 3: Create Sitemap Controller

**Command:**
```bash
php artisan make:controller SitemapController --no-interaction
```

**File:** `app/Http/Controllers/SitemapController.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    public function index()
    {
        if (! config('seo.sitemap.enabled', true)) {
            abort(404);
        }

        $sitemap = Cache::remember('sitemap', config('seo.sitemap.cache_duration', 3600), function () {
            return $this->generateSitemap();
        });

        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml');
    }

    protected function generateSitemap(): string
    {
        $urls = $this->getSitemapUrls();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $xml .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml">'.PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>'.PHP_EOL;
            $xml .= '    <loc>'.htmlspecialchars($url['loc']).'</loc>'.PHP_EOL;

            if (isset($url['lastmod'])) {
                $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'.PHP_EOL;
            }

            if (isset($url['changefreq'])) {
                $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'.PHP_EOL;
            }

            if (isset($url['priority'])) {
                $xml .= '    <priority>'.$url['priority'].'</priority>'.PHP_EOL;
            }

            $xml .= '  </url>'.PHP_EOL;
        }

        $xml .= '</urlset>';

        return $xml;
    }

    protected function getSitemapUrls(): array
    {
        $urls = [];

        // Homepage
        $urls[] = [
            'loc' => URL::to('/'),
            'lastmod' => now()->toW3cString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Add static pages
        $staticPages = $this->getStaticPages();
        foreach ($staticPages as $page) {
            $urls[] = array_merge([
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ], $page);
        }

        // Add dynamic content URLs
        $dynamicUrls = $this->getDynamicUrls();
        $urls = array_merge($urls, $dynamicUrls);

        return $urls;
    }

    protected function getStaticPages(): array
    {
        $pages = [];

        // Add your static routes here - CUSTOMIZE THIS
        $staticRoutes = [
            'about',
            'contact',
            // Add more routes as needed
        ];

        foreach ($staticRoutes as $route) {
            if (Route::has($route)) {
                $pages[] = [
                    'loc' => route($route),
                    'lastmod' => now()->toW3cString(),
                ];
            }
        }

        return $pages;
    }

    protected function getDynamicUrls(): array
    {
        $urls = [];

        // CUSTOMIZE THIS SECTION based on your models
        // Example for Blog Posts:
        /*
        if (class_exists(\App\Models\Post::class)) {
            try {
                $posts = \App\Models\Post::query()
                    ->where('published', true)
                    ->get(['slug', 'updated_at']);

                foreach ($posts as $post) {
                    $urls[] = [
                        'loc' => url('/blog/' . $post->slug),
                        'lastmod' => $post->updated_at->toW3cString(),
                        'changefreq' => 'monthly',
                        'priority' => '0.7',
                    ];
                }
            } catch (\Exception $e) {
                // Skip if table doesn't exist
            }
        }
        */

        return $urls;
    }

    public function clearCache()
    {
        Cache::forget('sitemap');

        return response()->json([
            'message' => 'Sitemap cache cleared successfully',
        ]);
    }
}
```

---

## Step 4: Create Blade Components

### 4.1 Main SEO Meta Component

**File:** `resources/views/components/seo-meta.blade.php`

```blade
{{-- SEO Meta Tags Component --}}
@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'image' => null,
    'canonical' => null,
    'type' => 'website',
    'noindex' => false,
])

@php
    $seoService = app(\App\Services\SeoService::class);
    
    if ($title) {
        $seoService->setTitle($title);
    }
    
    if ($description) {
        $seoService->setDescription($description);
    }
    
    if ($keywords) {
        $seoService->setKeywords($keywords);
    }
    
    if ($image) {
        $seoService->setImage($image);
    }
    
    if ($canonical) {
        $seoService->setCanonical($canonical);
    }
    
    if ($type) {
        $seoService->setType($type);
    }
    
    if ($noindex) {
        $seoService->noIndex();
    }
    
    $meta = $seoService->getMeta();
    $og = $seoService->getOpenGraph();
    $twitter = $seoService->getTwitter();
@endphp

{{-- Basic Meta Tags --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Title --}}
<title>{{ $meta['title'] ?? config('app.name') }}</title>

{{-- Meta Description & Keywords --}}
@if(isset($meta['description']))
    <meta name="description" content="{{ $meta['description'] }}">
@endif

@if(isset($meta['keywords']))
    <meta name="keywords" content="{{ $meta['keywords'] }}">
@endif

{{-- Robots --}}
<meta name="robots" content="{{ $meta['robots'] ?? 'index, follow' }}">

{{-- Canonical URL --}}
@if(isset($meta['canonical']))
    <link rel="canonical" href="{{ $meta['canonical'] }}">
@endif

{{-- Open Graph Tags --}}
@foreach($og as $property => $content)
    @if($content)
        <meta property="og:{{ $property }}" content="{{ $content }}">
    @endif
@endforeach

{{-- Twitter Card Tags --}}
@foreach($twitter as $name => $content)
    @if($content)
        <meta name="twitter:{{ $name }}" content="{{ $content }}">
    @endif
@endforeach

{{-- Verification Meta Tags --}}
@if(config('seo.verification.google'))
    <meta name="google-site-verification" content="{{ config('seo.verification.google') }}">
@endif

@if(config('seo.verification.bing'))
    <meta name="msvalidate.01" content="{{ config('seo.verification.bing') }}">
@endif

@if(config('seo.verification.yandex'))
    <meta name="yandex-verification" content="{{ config('seo.verification.yandex') }}">
@endif

@if(config('seo.verification.pinterest'))
    <meta name="p:domain_verify" content="{{ config('seo.verification.pinterest') }}">
@endif

{{-- Favicon --}}
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

{{-- Language Alternatives --}}
@if(isset($alternates) && is_array($alternates))
    @foreach($alternates as $locale => $url)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
    @endforeach
@endif

{{-- Additional Custom Meta Tags --}}
{{ $slot }}
```

### 4.2 Schema.org Base Component

**File:** `resources/views/components/schema-org.blade.php`

```blade
{{-- JSON-LD Structured Data Component --}}
@props([
    'schemas' => [],
])

@php
    $seoService = app(\App\Services\SeoService::class);
    
    // Add base schemas
    $baseSchemas = [
        $seoService->getOrganizationSchema(),
        $seoService->getWebsiteSchema(),
    ];
    
    // Merge with any additional schemas passed
    $allSchemas = array_merge($baseSchemas, $schemas, $seoService->getSchemas());
@endphp

@foreach($allSchemas as $schema)
    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endforeach
```

### 4.3 Schema Components (Create directory first)

**Create directory:**
```bash
mkdir -p resources/views/components/schema
```

#### Article Schema
**File:** `resources/views/components/schema/article.blade.php`

```blade
{{-- Article Schema Component --}}
@props([
    'title',
    'description',
    'image',
    'datePublished',
    'dateModified' => null,
    'author' => null,
    'authorUrl' => null,
])

@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $title,
        'description' => $description,
        'image' => is_array($image) ? $image : [$image],
        'datePublished' => $datePublished instanceof \DateTimeInterface 
            ? $datePublished->format('c') 
            : $datePublished,
        'dateModified' => $dateModified instanceof \DateTimeInterface 
            ? $dateModified->format('c') 
            : ($dateModified ?? $datePublished),
        'author' => [
            '@type' => 'Person',
            'name' => $author ?? config('seo.company_name'),
            'url' => $authorUrl ?? config('app.url'),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => config('seo.organization.name'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => url(config('seo.organization.logo')),
            ],
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => url()->current(),
        ],
    ];
@endphp

<script type="application/ld+json">
    {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
```

#### Product Schema
**File:** `resources/views/components/schema/product.blade.php`

```blade
{{-- Product Schema Component --}}
@props([
    'name',
    'description',
    'image',
    'price',
    'currency' => 'USD',
    'availability' => 'InStock',
    'sku' => null,
    'brand' => null,
    'rating' => null,
    'ratingCount' => null,
])

@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $name,
        'description' => $description,
        'image' => is_array($image) ? $image : [$image],
        'offers' => [
            '@type' => 'Offer',
            'price' => $price,
            'priceCurrency' => $currency,
            'availability' => 'https://schema.org/' . $availability,
            'url' => url()->current(),
        ],
    ];
    
    if ($sku) {
        $schema['sku'] = $sku;
    }
    
    if ($brand) {
        $schema['brand'] = [
            '@type' => 'Brand',
            'name' => $brand,
        ];
    }
    
    if ($rating && $ratingCount) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $rating,
            'reviewCount' => $ratingCount,
        ];
    }
@endphp

<script type="application/ld+json">
    {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
```

#### Breadcrumb Schema
**File:** `resources/views/components/schema/breadcrumb.blade.php`

```blade
{{-- Breadcrumb Schema Component --}}
@props([
    'items' => [],
])

@php
    $listItems = [];
    foreach ($items as $index => $item) {
        $listItems[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $item['url'] ?? null,
        ];
    }
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $listItems,
    ];
@endphp

<script type="application/ld+json">
    {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
```

### 4.4 Robots.txt Template

**File:** `resources/views/seo/robots.blade.php`

```blade
# robots.txt for {{ config('app.name') }}
User-agent: *
Disallow: /admin
Disallow: /storage/private
Disallow: /api/internal
Disallow: /vendor

# Allow public storage
Allow: /storage/public

# Sitemap
Sitemap: {{ config('app.url') }}/sitemap.xml

# Common crawlers
User-agent: Googlebot
Allow: /
Disallow: /admin

User-agent: Bingbot
Allow: /
Disallow: /admin
```

---

## Step 5: Create Helper Functions

**File:** `app/Helpers/seo_helpers.php`

```php
<?php

if (! function_exists('seo')) {
    /**
     * Get the SEO service instance
     */
    function seo(): \App\Services\SeoService
    {
        return app(\App\Services\SeoService::class);
    }
}

if (! function_exists('set_seo')) {
    /**
     * Quickly set SEO data for a page
     */
    function set_seo(?string $title = null, ?string $description = null, ?string $image = null): \App\Services\SeoService
    {
        $seo = seo();

        if ($title) {
            $seo->setTitle($title);
        }

        if ($description) {
            $seo->setDescription($description);
        }

        if ($image) {
            $seo->setImage($image);
        }

        return $seo;
    }
}

if (! function_exists('seo_breadcrumb')) {
    /**
     * Generate breadcrumb items for SEO
     */
    function seo_breadcrumb(array $items): array
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
        ];

        return array_merge($breadcrumbs, $items);
    }
}

if (! function_exists('clear_sitemap_cache')) {
    /**
     * Clear the sitemap cache
     */
    function clear_sitemap_cache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('sitemap');
    }
}
```

**Update composer.json to autoload helpers:**

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    },
    "files": [
        "app/Helpers/seo_helpers.php"
    ]
},
```

**Run:**
```bash
composer dump-autoload
```

---

## Step 6: Update Routes

**File:** `routes/web.php`

Add these routes at the end of the file:

```php
use App\Http\Controllers\SitemapController;

// SEO Routes
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    return response(view('seo.robots')->render(), 200)->header('Content-Type', 'text/plain');
})->name('robots');
```

---

## Step 7: Update Head Template

Update your main head partial or layout to use the SEO components.

**Option 1: If you have `resources/views/partials/head.blade.php`:**

```blade
{{-- SEO Meta Tags --}}
<x-seo-meta 
    :title="$title ?? null"
    :description="$description ?? null"
    :keywords="$keywords ?? null"
    :image="$image ?? null"
    :canonical="$canonical ?? null"
    :type="$type ?? 'website'"
    :noindex="$noindex ?? false"
>
    {{-- Additional verification or custom meta tags can be added here --}}
</x-seo-meta>

{{-- JSON-LD Structured Data --}}
<x-schema-org :schemas="$schemas ?? []" />

{{-- Your existing assets (fonts, vite, etc.) --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{ $styles ?? '' }}
```

**Option 2: If you have a layout file:**

Add to your `<head>` section:

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <x-seo-meta 
        :title="$title ?? null"
        :description="$description ?? null"
    />
    
    <x-schema-org :schemas="$schemas ?? []" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>
```

---

## Step 8: Create Tests

**File:** `tests/Feature/SeoImplementationTest.php`

```php
<?php

declare(strict_types=1);

use App\Services\SeoService;

describe('SEO Implementation', function () {
    
    test('sitemap route exists and returns xml', function () {
        $response = $this->get('/sitemap.xml');
        
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml');
        
        expect($response->getContent())
            ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
            ->toContain('<urlset')
            ->toContain('</urlset>');
    });

    test('robots.txt route exists and returns text', function () {
        $response = $this->get('/robots.txt');
        
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/plain');
        
        expect($response->getContent())
            ->toContain('User-agent:')
            ->toContain('Sitemap:');
    });

    test('seo service can be instantiated', function () {
        $seo = app(SeoService::class);
        
        expect($seo)->toBeInstanceOf(SeoService::class);
    });

    test('seo service sets title correctly', function () {
        $seo = app(SeoService::class);
        $seo->setTitle('Test Page');
        
        $meta = $seo->getMeta();
        
        expect($meta['title'])->toContain('Test Page');
    });

    test('seo helper function works', function () {
        $seo = seo();
        
        expect($seo)->toBeInstanceOf(SeoService::class);
    });

    test('set_seo helper function works', function () {
        $seo = set_seo('Helper Test', 'Helper Description');
        
        expect($seo)->toBeInstanceOf(SeoService::class);
        
        $meta = $seo->getMeta();
        expect($meta['title'])->toContain('Helper Test');
    });
});
```

**Run tests:**
```bash
php artisan test --filter=SeoImplementationTest
```

---

## Step 9: Configuration & Usage

### 9.1 Environment Variables

Add to your `.env` file:

```env
# SEO Configuration
SEO_SITE_NAME="Your Site Name"
SEO_SITE_TITLE="Your Site Title"
SEO_SITE_DESCRIPTION="Your site description here"
SEO_DEFAULT_IMAGE="/images/og-default.jpg"

# Social Media
SOCIAL_FACEBOOK="https://facebook.com/yourpage"
SOCIAL_TWITTER="https://twitter.com/yourhandle"

# Verification
GOOGLE_SITE_VERIFICATION="your-google-code"
```

### 9.2 Create Default Images

Create these images:
- `public/images/og-default.jpg` (1200x630px recommended)
- `public/images/logo.png` (square, transparent background)

### 9.3 Usage Examples

#### Basic Page
```blade
@extends('layouts.app', [
    'title' => 'About Us',
    'description' => 'Learn about our company',
])
```

#### Using Helper in Controller
```php
public function show(Product $product)
{
    set_seo(
        title: $product->name,
        description: $product->description,
        image: $product->image_url
    );
    
    return view('products.show', compact('product'));
}
```

#### With Schema Markup
```blade
<x-seo-meta :title="$post->title" :description="$post->excerpt" />

<x-schema.article
    :title="$post->title"
    :description="$post->excerpt"
    :image="$post->featured_image"
    :datePublished="$post->published_at"
    :author="$post->author->name"
/>
```

---

## Validation & Testing

### 1. Google Rich Results Test
- URL: https://search.google.com/test/rich-results
- Test your pages for structured data

### 2. Facebook Sharing Debugger
- URL: https://developers.facebook.com/tools/debug/
- Test Open Graph tags

### 3. Twitter Card Validator
- URL: https://cards-dev.twitter.com/validator
- Test Twitter Card implementation

### 4. Schema.org Validator
- URL: https://validator.schema.org/
- Validate JSON-LD markup

### 5. Lighthouse SEO Audit
```bash
npm install -g @lhci/cli
lhci autorun --collect.url=http://yoursite.test
```

---

## Customization Guide

### Add Custom Schema Component

1. Create component file:
```bash
mkdir -p resources/views/components/schema
touch resources/views/components/schema/custom-type.blade.php
```

2. Add schema markup following existing patterns

### Extend Sitemap

Edit `SitemapController.php` → `getDynamicUrls()` method and add your models:

```php
protected function getDynamicUrls(): array
{
    $urls = [];
    
    // Example: Add blog posts
    $posts = \App\Models\Post::published()->get();
    foreach ($posts as $post) {
        $urls[] = [
            'loc' => route('posts.show', $post),
            'lastmod' => $post->updated_at->toW3cString(),
            'changefreq' => 'monthly',
            'priority' => '0.7',
        ];
    }
    
    return $urls;
}
```

### Clear Sitemap Cache After Updates

In your model observers or controllers:

```php
// After creating/updating content
clear_sitemap_cache();
```

---

## Checklist

- [ ] All files created
- [ ] composer.json updated and `composer dump-autoload` run
- [ ] Routes added to `web.php`
- [ ] Environment variables configured
- [ ] Default OG images created
- [ ] Head template updated
- [ ] Tests passing
- [ ] Sitemap customized for your models
- [ ] Validated with Google Rich Results Test
- [ ] Validated with Facebook Debugger
- [ ] Lighthouse SEO score checked

---

## Best Practices

1. **Titles**: Keep under 60 characters
2. **Descriptions**: 150-160 characters optimal
3. **Images**: Use 1200x630px for Open Graph
4. **HTTPS**: Always use HTTPS in production
5. **Canonical URLs**: Set on all pages
6. **Mobile-First**: Ensure responsive design
7. **Page Speed**: Optimize for fast loading
8. **Cache**: Clear sitemap cache after content updates
9. **Monitor**: Use Google Search Console
10. **Test**: Validate before launch

---

## Troubleshooting

**Meta tags not showing?**
```bash
php artisan view:clear
php artisan config:clear
```

**Sitemap not updating?**
```php
clear_sitemap_cache();
```

**Schema validation errors?**
- Use Google Rich Results Test to identify issues
- Ensure all required fields are present
- Check JSON-LD syntax

---

## Additional Resources

- [Google Search Central](https://developers.google.com/search)
- [Schema.org Documentation](https://schema.org/)
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Cards Guide](https://developer.twitter.com/en/docs/twitter-for-websites/cards)

---

**That's it! Your Laravel project now has comprehensive SEO implementation. 🚀**
