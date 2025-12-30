@php
    $content = $data['content'] ?? '';
    $alignment = $data['alignment'] ?? 'left';
@endphp

@if($content)
    <p class="cms-paragraph text-{{ $alignment }}">
        {{ $content }}
    </p>
@endif
