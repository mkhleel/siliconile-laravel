@props([
    'hover' => true,
    'padding' => true,
])

@php
$classes = 'rounded-lg border bg-card text-card-foreground shadow-sm';
$classes .= $hover ? ' group hover:shadow-lg transition-all duration-300' : '';
$classes .= $padding ? ' p-6' : '';
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
