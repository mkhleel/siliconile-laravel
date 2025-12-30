@if($content)
    @php
        $alignmentClass = match ($alignment) {
            'center' => 'text-center',
            'right' => 'text-right',
            default => 'text-left',
        };
        
        $styleClass = match ($style) {
            'emphasis' => 'cms-quote-emphasis',
            'minimal' => 'cms-quote-minimal',
            'bordered' => 'cms-quote-bordered',
            default => 'cms-quote-default',
        };
    @endphp
    
    <blockquote class="cms-quote {{ $styleClass }} {{ $alignmentClass }} my-6">
        <p class="quote-content">
            "{{ $content }}"
        </p>
        
        @if($author || $source)
            <footer class="quote-attribution">
                — 
                @if($author)
                    <span class="quote-author">{{ $author }}</span>
                @endif
                @if($source)
                    @if($author), @endif
                    <cite class="quote-source">{{ $source }}</cite>
                @endif
            </footer>
        @endif
    </blockquote>
@endif

<style>
.cms-quote {
    position: relative;
    margin: 1.5rem 0;
}

.cms-quote .quote-content {
    font-size: 1.125rem;
    line-height: 1.6;
    font-style: italic;
    margin-bottom: 0.75rem;
}

.cms-quote .quote-attribution {
    font-size: 0.875rem;
    color: #6b7280;
    font-style: normal;
}

.cms-quote .quote-author {
    font-weight: 600;
}

.cms-quote .quote-source {
    font-style: italic;
}

/* Default style */
.cms-quote-default {
    border-left: 4px solid #3b82f6;
    padding-left: 1.5rem;
    color: #374151;
}

/* Emphasis style */
.cms-quote-emphasis {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    padding: 1.5rem;
    border-radius: 0.5rem;
    border-left: 4px solid #f59e0b;
    color: #1f2937;
}

.cms-quote-emphasis .quote-content {
    font-size: 1.25rem;
    font-weight: 500;
}

/* Minimal style */
.cms-quote-minimal {
    color: #6b7280;
    font-size: 1rem;
}

.cms-quote-minimal .quote-content {
    font-size: inherit;
}

/* Bordered style */
.cms-quote-bordered {
    border: 2px solid #e5e7eb;
    padding: 1.5rem;
    border-radius: 0.5rem;
    background: #f9fafb;
    color: #374151;
}

.cms-quote-bordered::before {
    content: '"';
    position: absolute;
    top: -0.5rem;
    left: 1rem;
    font-size: 4rem;
    color: #d1d5db;
    font-family: serif;
    line-height: 1;
}
</style>
