<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Siliconile | Empowering Egypt\'s Next Generation of Tech Startups' }}</title>
    <meta name="description" content="{{ $description ?? 'Leading startup incubator in Luxor, Egypt. We provide coworking spaces, funding, technical support, mentorship, and workshops to help tech startups grow and succeed.' }}">
    <meta name="keywords" content="{{ $keywords ?? 'startup incubator, Egypt, Luxor, tech startups, coworking, funding, mentorship' }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('theme/assets/images/favicon.svg') }}">
    
    @vite(['resources/css/theme.css', 'resources/js/theme.js'])
    
    {{ $styles ?? '' }}
</head>
<body class="antialiased min-h-screen flex flex-col">
    <x-header />

    <main class="flex-1">
        {{ $slot }}
    </main>

    <x-footer />

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{ $scripts ?? '' }}
</body>
</html>
