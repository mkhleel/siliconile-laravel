{{-- Event Schema Component --}}
@props([
    'name',
    'description',
    'image',
    'startDate',
    'endDate' => null,
    'location' => null,
    'locationName' => null,
    'locationAddress' => null,
    'organizer' => null,
    'organizerUrl' => null,
    'price' => null,
    'currency' => 'EGP',
    'availability' => 'InStock',
])

@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $name,
        'description' => $description,
        'image' => is_array($image) ? $image : [$image],
        'startDate' => $startDate instanceof \DateTimeInterface 
            ? $startDate->format('c') 
            : $startDate,
    ];
    
    if ($endDate) {
        $schema['endDate'] = $endDate instanceof \DateTimeInterface 
            ? $endDate->format('c') 
            : $endDate;
    }
    
    if ($location || $locationName) {
        $schema['location'] = [
            '@type' => 'Place',
            'name' => $locationName ?? $location,
        ];
        
        if ($locationAddress) {
            $schema['location']['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $locationAddress,
            ];
        }
    }
    
    if ($organizer) {
        $schema['organizer'] = [
            '@type' => 'Organization',
            'name' => $organizer,
            'url' => $organizerUrl ?? config('app.url'),
        ];
    }
    
    if ($price !== null) {
        $schema['offers'] = [
            '@type' => 'Offer',
            'price' => $price,
            'priceCurrency' => $currency,
            'availability' => 'https://schema.org/' . $availability,
            'url' => url()->current(),
        ];
    }
@endphp

<script type="application/ld+json">
    {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
