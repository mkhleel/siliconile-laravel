@if($content)
    @php
        $alignmentClass = match ($alignment) {
            'center' => 'text-center',
            'right' => 'text-right',
            'justify' => 'text-justify',
            default => 'text-left',
        };
        
        $sizeClass = match ($size) {
            'small' => 'text-sm',
            'large' => 'text-lg',
            default => 'text-base',
        };
    @endphp
    
    <div class="cms-paragraph {{ $alignmentClass }} {{ $sizeClass }} mb-4">
        {!! $content !!}
    </div>
@endif

<style>
.cms-paragraph {
    line-height: 1.6;
    color: #374151;
}

.cms-paragraph p {
    margin-bottom: 1rem;
}

.cms-paragraph p:last-child {
    margin-bottom: 0;
}

.cms-paragraph a {
    color: #3b82f6;
    text-decoration: underline;
}

.cms-paragraph a:hover {
    color: #1d4ed8;
}

.cms-paragraph strong {
    font-weight: 600;
}

.cms-paragraph blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1rem;
    margin: 1rem 0;
    font-style: italic;
    color: #6b7280;
}

.cms-paragraph ul, .cms-paragraph ol {
    margin: 1rem 0;
    padding-left: 2rem;
}

.cms-paragraph li {
    margin-bottom: 0.5rem;
}
</style>
