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
