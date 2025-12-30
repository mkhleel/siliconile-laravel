{{-- SEO Meta Tags Component --}}
@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'image' => null,
    'canonical' => null,
    'type' => 'website',
    'noindex' => false,
])

@php
    $seoService = app(\App\Services\SeoService::class);
    
    if ($title) {
        $seoService->setTitle($title);
    }
    
    if ($description) {
        $seoService->setDescription($description);
    }
    
    if ($keywords) {
        $seoService->setKeywords($keywords);
    }
    
    if ($image) {
        $seoService->setImage($image);
    }
    
    if ($canonical) {
        $seoService->setCanonical($canonical);
    }
    
    if ($type) {
        $seoService->setType($type);
    }
    
    if ($noindex) {
        $seoService->noIndex();
    }
    
    $meta = $seoService->getMeta();
    $og = $seoService->getOpenGraph();
    $twitter = $seoService->getTwitter();
@endphp

{{-- Basic Meta Tags --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Title --}}
<title>{{ $meta['title'] ?? config('app.name') }}</title>

{{-- Meta Description & Keywords --}}
@if(isset($meta['description']))
    <meta name="description" content="{{ $meta['description'] }}">
@endif

@if(isset($meta['keywords']))
    <meta name="keywords" content="{{ $meta['keywords'] }}">
@endif

{{-- Robots --}}
<meta name="robots" content="{{ $meta['robots'] ?? 'index, follow' }}">

{{-- Canonical URL --}}
@if(isset($meta['canonical']))
    <link rel="canonical" href="{{ $meta['canonical'] }}">
@endif

{{-- Open Graph Tags --}}
@foreach($og as $property => $content)
    @if($content)
        <meta property="og:{{ $property }}" content="{{ $content }}">
    @endif
@endforeach

{{-- Twitter Card Tags --}}
@foreach($twitter as $name => $content)
    @if($content)
        <meta name="twitter:{{ $name }}" content="{{ $content }}">
    @endif
@endforeach
<link rel="icon" type="image/svg+xml" href="{{ asset('theme/assets/images/favicon.svg') }}">

{{-- Verification Meta Tags --}}
@if(config('seo.verification.google'))
    <meta name="google-site-verification" content="{{ config('seo.verification.google') }}">
@endif

@if(config('seo.verification.bing'))
    <meta name="msvalidate.01" content="{{ config('seo.verification.bing') }}">
@endif

@if(config('seo.verification.yandex'))
    <meta name="yandex-verification" content="{{ config('seo.verification.yandex') }}">
@endif

@if(config('seo.verification.pinterest'))
    <meta name="p:domain_verify" content="{{ config('seo.verification.pinterest') }}">
@endif

<link rel="manifest" href="{{ asset('manifest.json') }}">
{{-- Favicon --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

{{-- Language Alternatives --}}
@if(isset($alternates) && is_array($alternates))
    @foreach($alternates as $locale => $url)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
    @endforeach
@endif

{{-- Additional Custom Meta Tags --}}
{{ $slot }}
