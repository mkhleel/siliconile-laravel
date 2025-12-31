@props([
    'title' => '',
    'description' => '',
    'category' => null,
    'date' => null,
    'time' => null,
    'location' => null,
    'image' => null,
    'action' => null,
    'actionUrl' => '#',
])

<x-ui.card :hover="true">
    @if($image)
        <img src="{{ $image }}" alt="{{ $title }}" class="w-full h-48 object-cover rounded-t-lg -mt-6 -mx-6 mb-6 w-[calc(100%+3rem)]" loading="lazy" />
    @endif
    
    <div class="space-y-4">
        @if($category)
            <div class="text-primary font-semibold text-sm uppercase tracking-wide">{{ $category }}</div>
        @endif
        
        <h3 class="text-2xl font-semibold leading-tight group-hover:text-primary transition-colors">{{ $title }}</h3>
        
        @if($description)
            <p class="text-muted-foreground">{{ $description }}</p>
        @endif
        
        <div class="space-y-2 pt-2">
            @if($date)
                <div class="flex items-center gap-3 text-sm">
                    <svg class="h-4 w-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>{{ $date }}</span>
                </div>
            @endif
            
            @if($time)
                <div class="flex items-center gap-3 text-sm">
                    <svg class="h-4 w-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ $time }}</span>
                </div>
            @endif
            
            @if($location)
                <div class="flex items-center gap-3 text-sm">
                    <svg class="h-4 w-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>{{ $location }}</span>
                </div>
            @endif
        </div>
        
        @if($action)
            <div class="pt-4">
                <x-ui.button :href="$actionUrl" variant="outline" size="sm">
                    {{ $action }}
                </x-ui.button>
            </div>
        @endif
    </div>
</x-ui.card>
