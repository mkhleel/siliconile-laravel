@props([
    'icon' => 'default',
    'title' => '',
    'description' => '',
    'features' => [],
    'badge' => null,
    'price' => null,
    'priceUnit' => '/month',
    'action' => null,
    'actionUrl' => '#',
    'featured' => false,
])

@php
$icons = [
    'wifi' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>',
    'desk' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>',
    'meeting' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>',
    'locker' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>',
    'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>',
    'x' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
    'default' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>',
];
@endphp

<x-ui.card :class="$featured ? 'border-primary border-2 relative' : ''" :hover="true">
    @if($featured)
        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
            <x-ui.badge variant="default">Most Popular</x-ui.badge>
        </div>
    @endif
    
    @if($badge)
        <div class="mb-4">
            <x-ui.badge>{{ $badge }}</x-ui.badge>
        </div>
    @endif
    
    <div class="space-y-4">
        <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
            <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $icons[$icon] ?? $icons['default'] !!}
            </svg>
        </div>
        
        <h3 class="text-2xl font-bold">{{ $title }}</h3>
        
        @if($price !== null)
            <div class="flex items-baseline gap-1">
                <span class="text-4xl font-bold text-primary">{{ $price }}</span>
                <span class="text-muted-foreground">{{ $priceUnit }}</span>
            </div>
        @endif
        
        @if($description)
            <p class="text-muted-foreground">{{ $description }}</p>
        @endif
        
        @if(count($features) > 0)
            <ul class="space-y-3 pt-4">
                @foreach($features as $feature)
                    <li class="flex items-center gap-3">
                        @if(is_array($feature) && isset($feature['included']))
                            @if($feature['included'])
                                <svg class="h-5 w-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $icons['check'] !!}
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-muted-foreground flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $icons['x'] !!}
                                </svg>
                            @endif
                            <span class="{{ $feature['included'] ? '' : 'text-muted-foreground line-through' }}">
                                {{ $feature['text'] }}
                            </span>
                        @else
                            <svg class="h-5 w-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {!! $icons['check'] !!}
                            </svg>
                            <span>{{ is_array($feature) ? $feature['text'] : $feature }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        
        @if($action)
            <div class="pt-6">
                <x-ui.button :href="$actionUrl" class="w-full" :variant="$featured ? 'primary' : 'outline'">
                    {{ $action }}
                </x-ui.button>
            </div>
        @endif
    </div>
</x-ui.card>
