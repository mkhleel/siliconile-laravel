@php
    $type = $data['type'] ?? 'ul';
    $items = $data['items'] ?? [];
    $style = $data['style'] ?? 'default';
    
    $styleClasses = [
        'default' => '',
        'spaced' => 'space-y-2',
        'compact' => 'space-y-0',
        'highlighted' => 'space-y-1'
    ];
@endphp

@if(!empty($items))
    @if($type === 'ol')
        <ol class="cms-list cms-list-ordered {{ $styleClasses[$style] ?? '' }}">
            @foreach($items as $item)
                @if(isset($item['content']) && !empty($item['content']))
                    <li class="cms-list-item">{{ $item['content'] }}</li>
                @endif
            @endforeach
        </ol>
    @else
        <ul class="cms-list cms-list-unordered {{ $styleClasses[$style] ?? '' }}">
            @foreach($items as $item)
                @if(isset($item['content']) && !empty($item['content']))
                    <li class="cms-list-item">{{ $item['content'] }}</li>
                @endif
            @endforeach
        </ul>
    @endif
@endif
