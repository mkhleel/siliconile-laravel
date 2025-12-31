@props([
    'stats' => [],
])

<section class="py-20 bg-muted/50">
    <div class="container px-4 md:px-6">
        <div class="grid gap-8 md:grid-cols-{{ count($stats) > 4 ? '4' : count($stats) }} text-center">
            @foreach($stats as $stat)
                <div class="space-y-2">
                    <div class="text-4xl md:text-6xl font-bold text-primary">{{ $stat['value'] }}</div>
                    <div class="text-lg font-semibold">{{ $stat['label'] }}</div>
                    @if(isset($stat['description']))
                        <div class="text-muted-foreground">{{ $stat['description'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
