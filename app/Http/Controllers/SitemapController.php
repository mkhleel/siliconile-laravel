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

        $cacheDuration = config('seo.sitemap.cache_duration', 3600);

        $sitemap = Cache::remember('sitemap', $cacheDuration, function () {
            return $this->generateSitemap();
        });

        return response($sitemap, 200)->header('Content-Type', 'application/xml');
    }

    protected function generateSitemap(): string
    {
        $urls = $this->getSitemapUrls();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . PHP_EOL;

            if (isset($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . PHP_EOL;
            }

            if (isset($url['changefreq'])) {
                $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
            }

            if (isset($url['priority'])) {
                $xml .= '    <priority>' . $url['priority'] . '</priority>' . PHP_EOL;
            }

            $xml .= '  </url>' . PHP_EOL;
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
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Add static pages
        $staticPages = $this->getStaticPages();
        foreach ($staticPages as $page) {
            $urls[] = array_merge([
                'changefreq' => 'monthly',
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
            'programs',
            'startups',
            'events',
            'coworking',
        ];

        foreach ($staticRoutes as $route) {
            if (Route::has($route)) {
                $pages[] = [
                    'loc' => route($route),
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
                $posts = \App\Models\Post::published()->get();
                foreach ($posts as $post) {
                    $urls[] = [
                        'loc' => route('posts.show', $post),
                        'lastmod' => $post->updated_at->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('Sitemap error: ' . $e->getMessage());
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
