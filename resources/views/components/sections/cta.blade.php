@props([
    'title' => '',
    'subtitle' => '',
    'primaryAction' => null,
    'primaryUrl' => null,
    'secondaryAction' => null,
    'secondaryUrl' => null,
])

<section class="py-20 md:py-32">
    <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
            <h2 class="text-3xl md:text-5xl font-bold">
                {!! $title !!}
            </h2>
            @if($subtitle)
                <p class="text-xl text-muted-foreground">{{ $subtitle }}</p>
            @endif
            
            @if($primaryAction || $secondaryAction)
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @if($primaryAction)
                        <x-ui.button :href="$primaryUrl" size="xl">
                            {{ $primaryAction }}
                            <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </x-ui.button>
                    @endif
                    @if($secondaryAction)
                        <x-ui.button :href="$secondaryUrl" variant="outline" size="xl">
                            {{ $secondaryAction }}
                        </x-ui.button>
                    @endif
                </div>
            @endif
            
            {{ $slot }}
        </div>
    </div>
</section>
