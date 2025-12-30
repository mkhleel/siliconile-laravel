@php
    $url = $data['url'] ?? '';
    $alt = $data['alt'] ?? '';
    $title = $data['title'] ?? '';
    $caption = $data['caption'] ?? '';
    $alignment = $data['alignment'] ?? 'center';
    $size = $data['size'] ?? 'large';
    
    $sizeClasses = [
        'small' => 'w-1/4',
        'medium' => 'w-1/2', 
        'large' => 'w-3/4',
        'full' => 'w-full'
    ];
    
    $alignmentClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right'
    ];
@endphp

@if($url)
    <figure class="cms-image {{ $alignmentClasses[$alignment] ?? 'text-center' }}">
        <img 
            src="{{ asset('storage/' . $url) }}" 
            alt="{{ $alt }}"
            @if($title) title="{{ $title }}" @endif
            class="cms-image-img {{ $sizeClasses[$size] ?? 'w-3/4' }} mx-auto"
        >
        @if($caption)
            <figcaption class="cms-image-caption mt-2 text-sm text-gray-600">
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
@endif
