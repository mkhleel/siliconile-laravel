@props([
    'variant' => 'default',
])

@php
$variants = [
    'default' => 'bg-primary/10 text-primary',
    'secondary' => 'bg-secondary text-secondary-foreground',
    'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    'danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    'outline' => 'border border-input bg-transparent text-foreground',
];

$classes = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors ' . ($variants[$variant] ?? $variants['default']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
