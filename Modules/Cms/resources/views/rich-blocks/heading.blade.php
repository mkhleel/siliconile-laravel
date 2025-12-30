@if($content)
    @php
        $alignmentClass = match ($alignment) {
            'center' => 'text-center',
            'right' => 'text-right',
            default => 'text-left',
        };
    @endphp
    
    <{{ $level }} class="cms-heading {{ $alignmentClass }} mb-4">
        {{ $content }}
    </{{ $level }}>
@endif

<style>
.cms-heading {
    font-weight: 700;
    line-height: 1.2;
    color: #1f2937;
}

.cms-heading.h1, .cms-heading h1 {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
}

.cms-heading.h2, .cms-heading h2 {
    font-size: 2rem;
    margin-bottom: 1.25rem;
}

.cms-heading.h3, .cms-heading h3 {
    font-size: 1.75rem;
    margin-bottom: 1rem;
}

.cms-heading.h4, .cms-heading h4 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.cms-heading.h5, .cms-heading h5 {
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
}

.cms-heading.h6, .cms-heading h6 {
    font-size: 1.125rem;
    margin-bottom: 0.75rem;
}
</style>
