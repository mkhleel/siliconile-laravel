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
