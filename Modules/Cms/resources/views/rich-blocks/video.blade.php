@if($url)
    @php
        $alignmentClass = match ($alignment) {
            'left' => 'text-left',
            'right' => 'text-right',
            default => 'text-center',
        };
        
        $maxWidthClass = match ($max_width) {
            'small' => 'max-w-sm',
            'medium' => 'max-w-lg',
            'large' => 'max-w-4xl',
            default => 'max-w-full',
        };
        
        $paddingBottom = match ($aspect_ratio) {
            '4:3' => 'pb-[75%]',
            '1:1' => 'pb-[100%]',
            '21:9' => 'pb-[42.86%]',
            default => 'pb-[56.25%]', // 16:9
        };
        
        // Extract video ID and create embed URL
        $embedUrl = $url;
        $isYouTube = str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be');
        $isVimeo = str_contains($url, 'vimeo.com');
        
        if ($isYouTube) {
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $url, $matches);
            $videoId = $matches[1] ?? '';
            if ($videoId) {
                $embedUrl = "https://www.youtube.com/embed/{$videoId}";
                if ($autoplay) $embedUrl .= '?autoplay=1';
                if (!$controls) $embedUrl .= ($autoplay ? '&' : '?') . 'controls=0';
            }
        } elseif ($isVimeo) {
            preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
            $videoId = $matches[1] ?? '';
            if ($videoId) {
                $embedUrl = "https://player.vimeo.com/video/{$videoId}";
                $params = [];
                if ($autoplay) $params[] = 'autoplay=1';
                if (!$controls) $params[] = 'controls=0';
                if (!empty($params)) $embedUrl .= '?' . implode('&', $params);
            }
        }
    @endphp
    
    <div class="cms-video {{ $alignmentClass }} my-6">
        <div class="inline-block w-full {{ $maxWidthClass }}">
            @if($title)
                <h4 class="text-lg font-semibold mb-3 text-gray-900">{{ $title }}</h4>
            @endif
            
            <div class="relative {{ $paddingBottom }} bg-gray-100 rounded-lg overflow-hidden shadow-lg">
                @if($isYouTube || $isVimeo)
                    <iframe 
                        src="{{ $embedUrl }}"
                        class="absolute inset-0 w-full h-full"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        loading="lazy"
                        title="{{ $title ?: 'Video' }}"
                    ></iframe>
                @else
                    {{-- Direct video file --}}
                    <video 
                        class="absolute inset-0 w-full h-full object-cover"
                        @if($controls) controls @endif
                        @if($autoplay) autoplay muted @endif
                        preload="metadata"
                    >
                        <source src="{{ $url }}" type="video/mp4">
                        <source src="{{ $url }}" type="video/webm">
                        <source src="{{ $url }}" type="video/ogg">
                        Your browser does not support the video tag.
                    </video>
                @endif
            </div>
            
            @if($isYouTube)
                <p class="text-xs text-gray-500 mt-2">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        YouTube Video
                    </span>
                </p>
            @elseif($isVimeo)
                <p class="text-xs text-gray-500 mt-2">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.977 6.416c-.105 2.338-1.739 5.543-4.894 9.609-3.268 4.247-6.026 6.37-8.29 6.37-1.409 0-2.578-1.294-3.553-3.881L5.322 11.4C4.603 8.816 3.834 7.522 3.01 7.522c-.179 0-.806.378-1.881 1.132L0 7.197c1.185-1.044 2.351-2.084 3.501-3.128C5.08 2.701 6.266 1.984 7.055 1.91c1.867-.18 3.016 1.1 3.447 3.838.465 2.953.789 4.789.971 5.507.539 2.45 1.131 3.674 1.776 3.674.502 0 1.256-.796 2.265-2.385 1.004-1.589 1.54-2.797 1.612-3.628.144-1.371-.395-2.061-1.614-2.061-.574 0-1.167.121-1.777.391 1.186-3.868 3.434-5.757 6.762-5.637 2.473.06 3.628 1.664 3.493 4.797l-.013.01z"/>
                        </svg>
                        Vimeo Video
                    </span>
                </p>
            @endif
        </div>
    </div>
@endif

<style>
.cms-video {
    position: relative;
}

.cms-video .max-w-sm {
    max-width: 24rem;
}

.cms-video .max-w-lg {
    max-width: 32rem;
}

.cms-video .max-w-4xl {
    max-width: 56rem;
}

.cms-video .max-w-full {
    max-width: 100%;
}

/* Responsive video container */
.cms-video .relative {
    position: relative;
    width: 100%;
    height: 0;
}

.cms-video iframe,
.cms-video video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* Padding bottom classes for aspect ratios */
.pb-\[56\.25\%\] {
    padding-bottom: 56.25%;
}

.pb-\[75\%\] {
    padding-bottom: 75%;
}

.pb-\[100\%\] {
    padding-bottom: 100%;
}

.pb-\[42\.86\%\] {
    padding-bottom: 42.86%;
}

/* Hover effects */
.cms-video .relative:hover {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    transition: box-shadow 0.3s ease;
}
</style>
