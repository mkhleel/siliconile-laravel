<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- SEO Meta Tags --}}
    <x-seo-meta 
        :title="$title ?? null"
        :description="$description ?? null"
        :keywords="$keywords ?? null"
        :image="$image ?? null"
        :canonical="$canonical ?? null"
        :type="$type ?? 'website'"
        :noindex="$noindex ?? false"
    />
    
    {{-- JSON-LD Structured Data --}}
    <x-schema-org :schemas="$schemas ?? []" />
    
    <link rel="icon" type="image/svg+xml" href="{{ asset('theme/assets/images/favicon.svg') }}">
    
    @vite(['resources/css/theme.css', 'resources/js/theme.js'])
    
    {{ $styles ?? '' }}
</head>
<body class="antialiased min-h-screen flex flex-col">
    <x-header />

        {{ $slot }}

    <x-footer />

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{ $scripts ?? '' }}
</body>
</html>
