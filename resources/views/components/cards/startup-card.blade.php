@props([
    'name' => '',
    'description' => '',
    'logo' => null,
    'initials' => null,
    'stage' => null,
    'funding' => null,
    'url' => null,
    'tags' => [],
])

<x-ui.card :hover="true">
    <div class="space-y-4">
        <div class="flex items-start gap-4">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $name }}" class="w-12 h-12 rounded-lg object-cover" />
            @else
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-lg font-bold text-primary">
                    {{ $initials ?? substr($name, 0, 2) }}
                </div>
            @endif
            
            <div class="flex-1 min-w-0">
                <h3 class="text-xl font-bold truncate group-hover:text-primary transition-colors">{{ $name }}</h3>
                @if($description)
                    <p class="text-sm text-muted-foreground line-clamp-2">{{ $description }}</p>
                @endif
            </div>
        </div>
        
        @if(count($tags) > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($tags as $tag)
                    <x-ui.badge variant="secondary">{{ $tag }}</x-ui.badge>
                @endforeach
            </div>
        @endif
        
        <div class="flex items-center justify-between pt-4 border-t text-sm">
            @if($stage)
                <div>
                    <span class="text-muted-foreground">Stage:</span>
                    <span class="font-semibold ml-1">{{ $stage }}</span>
                </div>
            @endif
            
            @if($funding)
                <div>
                    <span class="text-muted-foreground">Funding:</span>
                    <span class="font-semibold ml-1">{{ $funding }}</span>
                </div>
            @endif
        </div>
        
        @if($url)
            <a href="{{ $url }}" target="_blank" class="inline-flex items-center gap-2 text-sm text-primary hover:underline">
                Visit Website
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
        @endif
    </div>
</x-ui.card>
