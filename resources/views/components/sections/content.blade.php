@props([
    'title' => '',
    'subtitle' => '',
    'muted' => false,
])

<section {{ $attributes->merge(['class' => 'py-20 md:py-32' . ($muted ? ' bg-muted/50' : '')]) }}>
    <div class="container px-4 md:px-6">
        @if($title)
            <div class="text-center space-y-4 mb-16">
                <h2 class="text-3xl md:text-5xl font-bold">{!! $title !!}</h2>
                @if($subtitle)
                    <p class="text-xl text-muted-foreground max-w-2xl mx-auto">{{ $subtitle }}</p>
                @endif
            </div>
        @endif
        
        {{ $slot }}
    </div>
</section>
