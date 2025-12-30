@if($url)
    @php
        $alignmentClass = match ($alignment) {
            'left' => 'text-left',
            'right' => 'text-right',
            default => 'text-center',
        };
        
        $widthClass = match ($size) {
            'small' => 'max-w-sm',
            'medium' => 'max-w-md',
            'large' => 'max-w-2xl',
            default => 'max-w-full',
        };
        
        $imageUrl = str_starts_with($url, 'http') ? $url : asset('storage/' . $url);
    @endphp
    
    <div class="cms-image {{ $alignmentClass }} my-6">
        <div class="inline-block {{ $widthClass }}">
            <img 
                src="{{ $imageUrl }}" 
                alt="{{ $alt }}" 
                class="w-full h-auto rounded-lg shadow-sm"
                loading="lazy"
            />
            
            @if($caption)
                <p class="mt-2 text-sm text-gray-600 italic">
                    {{ $caption }}
                </p>
            @endif
        </div>
    </div>
@endif

<style>
.cms-image img {
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    transition: box-shadow 0.2s ease;
}

.cms-image img:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.cms-image .max-w-sm {
    max-width: 24rem;
}

.cms-image .max-w-md {
    max-width: 28rem;
}

.cms-image .max-w-2xl {
    max-width: 42rem;
}

.cms-image .max-w-full {
    max-width: 100%;
}
</style>
