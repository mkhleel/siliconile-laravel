@if($title && $button_text && $button_url)
    @php
        $alignmentClass = match ($alignment) {
            'left' => 'text-left',
            'right' => 'text-right',
            default => 'text-center',
        };
        
        $sizeClasses = match ($size) {
            'small' => 'py-6 px-6',
            'large' => 'py-12 px-8',
            default => 'py-8 px-6',
        };
        
        $bgColor = match ($style) {
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
            'custom' => $custom_color,
            default => '#3b82f6',
        };
        
        $target = $open_in_new_tab ? '_blank' : '_self';
        $rel = $open_in_new_tab ? 'noopener noreferrer' : '';
    @endphp
    
    <div class="cms-cta {{ $alignmentClass }} my-8">
        <div 
            class="cta-container {{ $sizeClasses }} rounded-xl shadow-lg"
            style="background: linear-gradient(135deg, {{ $bgColor }}15, {{ $bgColor }}05); border: 2px solid {{ $bgColor }}30;"
        >
            <div class="max-w-2xl mx-auto">
                <h3 class="cta-title text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                    {{ $title }}
                </h3>
                
                @if($description)
                    <p class="cta-description text-lg text-gray-600 mb-6 leading-relaxed">
                        {{ $description }}
                    </p>
                @endif
                
                <a 
                    href="{{ $button_url }}"
                    target="{{ $target }}"
                    @if($rel) rel="{{ $rel }}" @endif
                    class="cta-button inline-block px-8 py-4 text-lg font-semibold text-white rounded-lg transition-all duration-200 transform hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 no-underline"
                    style="background-color: {{ $bgColor }}; border-color: {{ $bgColor }}; focus:ring-color: {{ $bgColor }};"
                >
                    {{ $button_text }}
                    @if($open_in_new_tab)
                        <svg class="inline-block w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    @endif
                </a>
            </div>
        </div>
    </div>
@endif

<style>
.cms-cta {
    position: relative;
}

.cta-container {
    position: relative;
    overflow: hidden;
}

.cta-container::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: shimmer 3s ease-in-out infinite;
    pointer-events: none;
}

@keyframes shimmer {
    0%, 100% {
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
    }
    50% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }
}

.cta-title {
    position: relative;
    z-index: 1;
}

.cta-description {
    position: relative;
    z-index: 1;
}

.cta-button {
    position: relative;
    z-index: 1;
    text-decoration: none !important;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.cta-button:hover {
    text-decoration: none !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.cta-button:active {
    transform: translateY(0);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .cta-title {
        font-size: 1.5rem;
    }
    
    .cta-description {
        font-size: 1rem;
    }
    
    .cta-button {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .cta-title {
        color: #f9fafb;
    }
    
    .cta-description {
        color: #d1d5db;
    }
}
</style>
