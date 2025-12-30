<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
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

    @vite(['resources/css/theme.css', 'resources/js/theme.js'])
    
    {{ $styles ?? '' }}
</head>
<body class="antialiased min-h-screen flex flex-col">
    <x-header />

    <main class="flex-1">
        {{ $slot }}
    </main>

    <x-footer />
    
    {{ $scripts ?? '' }}
</body>
</html>
