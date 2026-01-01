@props([
    'name' => '',
    'description' => '',
    'type' => null,
    'capacity' => null,
    'hourlyRate' => null,
    'dailyRate' => null,
    'image' => null,
    'amenities' => [],
    'action' => 'Book Now',
    'actionUrl' => '#',
    'available' => true,
])

<x-ui.card :hover="true">
    @if($image)
        <img src="{{ $image }}" alt="{{ $name }}" class="w-full h-48 object-cover rounded-t-lg -mt-6 -mx-6 mb-6 w-[calc(100%+3rem)]" loading="lazy" />
    @else
        <div class="w-full h-48 bg-gradient-to-br from-primary/10 to-secondary/20 rounded-t-lg -mt-6 -mx-6 mb-6 w-[calc(100%+3rem)] flex items-center justify-center">
            <svg class="h-16 w-16 text-primary/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
    @endif
    
    <div class="space-y-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                @if($type)
                    <x-ui.badge class="mb-2">{{ $type }}</x-ui.badge>
                @endif
                <h3 class="text-xl font-bold group-hover:text-primary transition-colors">{{ $name }}</h3>
            </div>
            @if(!$available)
                <x-ui.badge variant="danger">Unavailable</x-ui.badge>
            @endif
        </div>
        
        @if($description)
            <p class="text-sm text-muted-foreground line-clamp-2">{{ $description }}</p>
        @endif
        
        <div class="flex flex-wrap gap-4 text-sm">
            @if($capacity)
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>{{ __('Up to') }} {{ $capacity }} people</span>
                </div>
            @endif
        </div>
        
        @if(count($amenities) > 0)
            <div class="flex flex-wrap gap-2">
                @foreach(array_slice($amenities, 0, 4) as $amenity)
                    <span class="text-xs bg-muted px-2 py-1 rounded">{{ $amenity }}</span>
                @endforeach
                @if(count($amenities) > 4)
                    <span class="text-xs text-muted-foreground">+{{ count($amenities) - 4 }} more</span>
                @endif
            </div>
        @endif
        
        <div class="flex items-center justify-between pt-4 border-t">
            <div class="space-y-1">
                @if($hourlyRate)
                    <div class="text-lg font-bold text-primary">{{ $hourlyRate }} <span class="text-sm font-normal text-muted-foreground">{{ __('/hour') }}</span></div>
                @endif
                @if($dailyRate)
                    <div class="text-sm text-muted-foreground">or {{ $dailyRate }}{{ __('/day') }}</div>
                @endif
            </div>
            
            @if($available)
                <x-ui.button :href="$actionUrl" size="sm">
                    {{ $action }}
                </x-ui.button>
            @endif
        </div>
    </div>
</x-ui.card>
