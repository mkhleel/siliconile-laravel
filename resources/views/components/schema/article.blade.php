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
