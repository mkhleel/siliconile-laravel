@props([
    'variant' => 'primary',
    'size' => 'default',
    'href' => null,
    'type' => 'button',
])

@php
$baseClasses = 'inline-flex items-center justify-center whitespace-nowrap rounded-md font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50';

$variants = [
    'primary' => 'bg-primary text-primary-foreground hover:bg-primary/90',
    'secondary' => 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
    'outline' => 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
    'ghost' => 'hover:bg-accent hover:text-accent-foreground',
    'destructive' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
    'link' => 'text-primary underline-offset-4 hover:underline',
];

$sizes = [
    'sm' => 'h-9 px-3 text-sm',
    'default' => 'h-10 px-4 py-2 text-sm',
    'lg' => 'h-11 px-8 text-lg',
    'xl' => 'h-12 px-10 py-6 text-lg',
    'icon' => 'h-10 w-10',
];

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['default']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
