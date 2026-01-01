@props(['class' => ''])

@php
    $currentLocale = app()->getLocale();
    $locales = [
        'en' => ['name' => 'English', 'flag' => '🇺🇸', 'dir' => 'ltr'],
        'ar' => ['name' => 'العربية', 'flag' => '🇪🇬', 'dir' => 'rtl'],
    ];
@endphp

<div 
    x-data="{ open: false }" 
    @click.away="open = false"
    class="relative {{ $class }}"
>
    <button 
        @click="open = !open" 
        type="button"
        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <span class="text-base">{{ $locales[$currentLocale]['flag'] }}</span>
        <span class="hidden sm:inline">{{ $locales[$currentLocale]['name'] }}</span>
        <svg 
            class="h-4 w-4 transition-transform duration-200" 
            :class="{ 'rotate-180': open }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 rtl:right-auto rtl:left-0 mt-2 w-40 origin-top-right rounded-md bg-background border shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
        role="menu"
        aria-orientation="vertical"
        x-cloak
    >
        <div class="py-1" role="none">
            @foreach($locales as $code => $locale)
                <a 
                    href="{{ url()->current() }}?lang={{ $code }}"
                    class="flex items-center gap-3 px-4 py-2 text-sm transition-colors {{ $currentLocale === $code ? 'bg-accent text-accent-foreground' : 'hover:bg-accent hover:text-accent-foreground' }}"
                    role="menuitem"
                    wire:navigate.hover
                >
                    <span class="text-base">{{ $locale['flag'] }}</span>
                    <span>{{ $locale['name'] }}</span>
                    @if($currentLocale === $code)
                        <svg class="ms-auto h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>
