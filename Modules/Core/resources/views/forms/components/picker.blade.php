@php
    use Filament\Support\Colors\Color;

    $options = $getOptions();
    $icons = $getIcons();
    $images = $getImages();
    $imageOnly = $getImageOnly();
    $imageSize = $getImageSize() ?: 50;
    $checkedColor = Color::Green[500];
    $multiple = $getMultiple();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :id="$getId()"
    :field="$field"
>
    <div
        {{ $attributes->merge($getExtraAttributes())->class(['ic-fo-picker']) }}
    >
        <div
            @if($multiple)
                x-data="{
                    state: $wire.{{ $applyStateBindingModifiers('entangle(\'' . $getStatePath() . '\')') }},
                    hoveredImage: null,
                    imagePosition: { x: 0, y: 0 },
                    init() {
                        if (!Array.isArray(this.state)) {
                            this.state = this.state ? [this.state] : [];
                        }
                    },
                    setState: function(value) {
                        if (this.state.includes(value)) {
                            this.state = this.state.filter(item => item !== value);
                        } else {
                            this.state.push(value);
                        }
                    },
                    showFullImage: function(imageSrc, event) {
                        this.hoveredImage = imageSrc;
                        this.updateImagePosition(event);
                    },
                    hideFullImage: function() {
                        this.hoveredImage = null;
                    },
                    updateImagePosition: function(event) {
                        const rect = event.target.getBoundingClientRect();
                        this.imagePosition = {
                            x: rect.right + 10,
                            y: rect.top
                        };
                    }
                }"
            @else
                x-data="{
                    state: $wire.{{ $applyStateBindingModifiers('entangle(\'' . $getStatePath() . '\')') }},
                    hoveredImage: null,
                    imagePosition: { x: 0, y: 0 },
                    setState: function(value) {
                        if(this.state == value){
                            this.state = ''
                            return
                        }
                        this.state = value;

                        {{-- this.$refs.input.value = value --}}
                    },
                    showFullImage: function(imageSrc, event) {
                        this.hoveredImage = imageSrc;
                        this.updateImagePosition(event);
                    },
                    hideFullImage: function() {
                        this.hoveredImage = null;
                    },
                    updateImagePosition: function(event) {
                        const rect = event.target.getBoundingClientRect();
                        this.imagePosition = {
                            x: rect.right + 10,
                            y: rect.top
                        };
                    }
                }"
            @endif
            class="flex flex-wrap gap-2 justify-around relative"
        >
            <input
                type="hidden"
                id="{{ $getId() }}"
                x-model="state"
                @if($multiple) x-init="init" @endif
            >

            <!-- Full size image overlay -->
            <div
                x-show="hoveredImage"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed z-50 pointer-events-none"
                :style="`left: ${imagePosition.x}px; top: ${imagePosition.y}px;`"
            >
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 p-2 max-w-md max-h-96 overflow-hidden">
                    <img
                        :src="hoveredImage"
                        class="max-w-full max-h-full object-contain rounded"
                        style="max-width: 400px; max-height: 350px;"
                    >
                </div>
            </div>

            <!-- Interact with the `state` property in Alpine.js -->
            @foreach($options as $value => $label)
                <button
                    type="button"
                    x-bind:class="@if($multiple) state.includes('{{ $value }}') @else state == '{{ $value }}' @endif
                            ? 'px-2 py-1 rounded shadow bg-primary-500 text-white relative'
                            : 'px-2 py-1 rounded text-gray-900 shadow relative dark:bg-gray-700'"
                    x-on:click="setState('{{ $value }}')"
                >
                    @if(filled($images))
                        @if($images[$value] && Str::endsWith($images[$value], '.pdf'))
                            @php($images[$value] = str_replace('.pdf', '.jpg', $images[$value]))
                            <img src="{{ url(($images[$value]) ) }}" alt="{{ $label }}"
                                 style="width:{{ $imageSize }}px; height:{{ $imageSize }}px;"
                                 draggable="false"
                                 @mouseenter="showFullImage('{{ url($images[$value]) }}', $event)"
                                 @mouseleave="hideFullImage()"
                                 @mousemove="updateImagePosition($event)"
                                 class="cursor-pointer">
                        @else
                            <img src="{{ url(($images[$value]) ) }}" alt="{{ $label }}"
                                 style="width:{{ $imageSize }}px; height:{{ $imageSize }}px;"
                                 draggable="false"
                                 @mouseenter="showFullImage('{{ url($images[$value]) }}', $event)"
                                 @mouseleave="hideFullImage()"
                                 @mousemove="updateImagePosition($event)"
                                 class="cursor-pointer">
                        @endif
                    @endif

                    <div class="flex items-center text-center">
                        @isset($icons[$value])
                            <x-filament::icon
                                icon="{{ $icons[$value] }}"
                                class="h-4 w-4 mr-2"
                            />
                        @endisset
                        @if(!$imageOnly || !filled($images))
                            {{ $label }}
                        @endif
                    </div>
                    <div class="absolute -right-2 -top-2" style="right:-.5rem;top:-.5rem;"
                         x-show="state.includes('{{ $value }}')">
                            <span style="color:rgb({{ $checkedColor }})">
                                <x-filament::icon
                                    icon="heroicon-s-check-circle"
                                    class="h-4 w-4"
                                />
                            </span>
                    </div>
                </button>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
