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
            'canonical' => URL::current(),
            'robots' => $this->getRobotsContent(),
        ];

        $this->openGraph = [
            'title' => config('seo.site_title'),
            'description' => config('seo.site_description'),
            'url' => URL::current(),
            'type' => 'website',
            'site_name' => config('seo.site_name'),
            'image' => $this->getAbsoluteUrl(config('seo.default_image')),
            'image:width' => config('seo.default_image_width'),
            'image:height' => config('seo.default_image_height'),
        ];

        $this->twitter = [
            'card' => config('seo.twitter_card_type'),
            'title' => config('seo.site_title'),
            'description' => config('seo.site_description'),
            'image' => $this->getAbsoluteUrl(config('seo.default_image')),
        ];
    }

    public function setTitle(string $title, bool $appendSiteName = true): self
    {
        $this->meta['title'] = $appendSiteName
            ? $title . ' - ' . config('seo.site_name')
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
            $this->openGraph['image:width'] = (string) $width;
        }

        if ($height) {
            $this->openGraph['image:height'] = (string) $height;
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
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => config('app.url') . '/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function getOrganizationSchema(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('seo.organization.name'),
            'url' => config('app.url'),
            'logo' => $this->getAbsoluteUrl(config('seo.organization.logo')),
        ];

        if (config('seo.contact_email')) {
            $schema['contactPoint'] = [
                '@type' => 'ContactPoint',
                'email' => config('seo.contact_email'),
                'contactType' => 'customer service',
            ];
        }

        $socialProfiles = array_filter(config('seo.social', []));
        if (!empty($socialProfiles)) {
            $schema['sameAs'] = array_values($socialProfiles);
        }

        $address = config('seo.organization.address');
        if (!empty(array_filter($address))) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['streetAddress'] ?? '',
                'addressLocality' => $address['addressLocality'] ?? '',
                'addressRegion' => $address['addressRegion'] ?? '',
                'postalCode' => $address['postalCode'] ?? '',
                'addressCountry' => $address['addressCountry'] ?? '',
            ];
        }

        return $schema;
    }

    public function getWebPageSchema(?string $title = null, ?string $description = null): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title ?? $this->meta['title'] ?? config('seo.site_title'),
            'description' => $description ?? $this->meta['description'] ?? config('seo.site_description'),
            'url' => URL::current(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('seo.organization.name'),
            ],
        ];
    }
}
