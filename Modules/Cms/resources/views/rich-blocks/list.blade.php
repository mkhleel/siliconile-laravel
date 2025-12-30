@if(!empty($items))
    <{{ $type }} class="cms-list my-4">
        @foreach($items as $item)
            @if(!empty($item['content']))
                <li class="mb-2">{{ $item['content'] }}</li>
            @endif
        @endforeach
    </{{ $type }}>
@endif

<style>
.cms-list {
    padding-left: 1.5rem;
    color: #374151;
    line-height: 1.6;
}

.cms-list li {
    margin-bottom: 0.5rem;
}

.cms-list li:last-child {
    margin-bottom: 0;
}

/* Ordered list styling */
ol.cms-list {
    list-style-type: decimal;
}

/* Unordered list styling */
ul.cms-list {
    list-style-type: disc;
}

/* Nested list styling */
.cms-list .cms-list {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.cms-list ul.cms-list {
    list-style-type: circle;
}

.cms-list ol.cms-list {
    list-style-type: lower-alpha;
}
</style>
