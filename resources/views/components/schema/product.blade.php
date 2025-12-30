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
