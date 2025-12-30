@php
    $level = $data['level'] ?? 'h2';
    $content = $data['content'] ?? '';
    $alignment = $data['alignment'] ?? 'left';
@endphp

@if($content)
    <{{ $level }} class="cms-heading text-{{ $alignment }}">
        {{ $content }}
    </{{ $level }}>
@endif
