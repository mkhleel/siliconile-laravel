@props([
    'title' => '',
    'subtitle' => '',
    'gradient' => true,
])

<section {{ $attributes->merge(['class' => 'py-20 md:py-32' . ($gradient ? ' bg-gradient-to-br from-primary/5 to-secondary/10' : '')]) }}>
    <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
            <h1 class="text-4xl md:text-6xl font-bold tracking-tight">
                {!! $title !!}
            </h1>
            @if($subtitle)
                <p class="text-xl md:text-2xl text-muted-foreground">
                    {{ $subtitle }}
                </p>
            @endif
            {{ $slot }}
        </div>
    </div>
</section>
