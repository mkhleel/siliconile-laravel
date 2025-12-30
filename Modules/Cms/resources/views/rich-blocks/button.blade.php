@if($text && $url)
    @php
        $alignmentClass = match ($alignment) {
            'center' => 'text-center',
            'right' => 'text-right',
            default => 'text-left',
        };
        
        $sizeClasses = match ($size) {
            'small' => 'px-3 py-2 text-sm',
            'large' => 'px-8 py-4 text-lg',
            default => 'px-6 py-3 text-base',
        };
        
        $styleClasses = match ($style) {
            'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white',
            'success' => 'bg-green-600 hover:bg-green-700 text-white',
            'danger' => 'bg-red-600 hover:bg-red-700 text-white',
            'outline' => 'border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white bg-transparent',
            'custom' => 'text-white',
            default => 'bg-blue-600 hover:bg-blue-700 text-white',
        };
        
        $customStyle = $style === 'custom' ? "background-color: {$custom_color}; border-color: {$custom_color};" : '';
        $target = $new_tab ? '_blank' : '_self';
        $rel = $new_tab ? 'noopener noreferrer' : '';
    @endphp
    
    <div class="cms-button {{ $alignmentClass }} my-4">
        <a 
            href="{{ $url }}" 
            target="{{ $target }}"
            @if($rel) rel="{{ $rel }}" @endif
            class="inline-block {{ $sizeClasses }} {{ $styleClasses }} font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 no-underline"
            @if($customStyle) style="{{ $customStyle }}" @endif
        >
            {{ $text }}
        </a>
    </div>
@endif

<style>
.cms-button a {
    text-decoration: none !important;
    display: inline-block;
    transition: all 0.2s ease;
}

.cms-button a:hover {
    text-decoration: none !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.cms-button a:active {
    transform: translateY(0);
}

/* Focus states for accessibility */
.cms-button a:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Custom hover effects for outline style */
.cms-button a.border-2:hover {
    background-color: #3b82f6 !important;
    color: white !important;
}
</style>
