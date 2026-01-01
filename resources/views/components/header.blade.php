@props(['currentRoute' => null])

@php
    $navItems = [
        ['route' => 'home', 'label' => __('frontend.nav.home')],
        ['route' => 'about', 'label' => __('frontend.nav.about')],
        ['route' => 'programs', 'label' => __('frontend.nav.programs')],
        ['route' => 'startups', 'label' => __('frontend.nav.startups')],
        ['route' => 'events.index', 'label' => __('frontend.nav.events')],
        ['route' => 'spaces', 'label' => __('frontend.nav.spaces')],
        ['route' => 'pricing', 'label' => __('frontend.nav.pricing')],
        ['route' => 'contact', 'label' => __('frontend.nav.contact')],
    ];
@endphp

<header 
    class="fixed top-0 z-50 w-full transition-all duration-300 ease-in-out" 
    x-data="{ 
        isOpen: false,
        scrolled: false,
        toggle() { this.isOpen = !this.isOpen },
        close() { this.isOpen = false },
        init() {
            this.scrolled = window.scrollY > 50;
            window.addEventListener('scroll', () => {
                this.scrolled = window.scrollY > 50;
            });
        }
    }"
    :class="scrolled ? 'bg-background/95 backdrop-blur border-b shadow-sm py-0' : 'bg-transparent py-2'"
>
    <div class="container flex items-center justify-between px-4 md:px-6 transition-all duration-300" :class="scrolled ? 'h-16' : 'h-20'">
        <a href="{{ route('home') }}" class="flex items-center space-x-2 rtl:space-x-reverse">
            <!-- Square logo (default - at top) -->
            <img 
                src="{{ asset('theme/assets/images/siliconile.svg') }}" 
                alt="{{ __('Siliconile Logo') }}" 
                class="w-auto transition-all duration-300"
                :class="scrolled ? 'h-0 opacity-0 w-0' : 'h-20 md:ps-5 rtl:md:pe-5 rtl:md:ps-0 opacity-100'"
            >
            <!-- Full logo (scrolled) -->
            <img 
                src="{{ asset('theme/assets/images/logo.svg') }}" 
                alt="Siliconile Logo" 
                class="w-auto transition-all duration-300"
                :class="scrolled ? 'h-10 opacity-100' : 'h-0 opacity-0 w-0'"
            >
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden lg:flex items-center space-x-6 rtl:space-x-reverse text-sm font-medium">
            @foreach($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    class="hover:text-primary transition-colors {{ request()->routeIs($item['route']) ? 'text-primary' : '' }}"
                    wire:navigate
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="hidden lg:flex items-center space-x-4 rtl:space-x-reverse">
            {{-- Language Switcher --}}
            <x-language-switcher />
            
            @auth
                <a wire:navigate href="{{ route('member.portal') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                    {{ __('frontend.nav.dashboard') }}
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                        {{ __('frontend.nav.logout') }}
                    </button>
                </form>
            @else
                <a wire:navigate href="{{ route('login') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                    {{ __('frontend.nav.login') }}
                </a>
                <a wire:navigate href="{{ route('apply') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 rounded-md px-3">
                    {{ __('frontend.nav.apply_now') }}
                </a>
            @endauth
        </div>

        <!-- Mobile menu button -->
        <button @click="toggle" class="lg:hidden inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 w-10">
            <svg x-show="!isOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg x-show="isOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile Navigation -->
    <div x-show="isOpen" x-collapse class="lg:hidden border-t bg-background">
        <nav class="flex flex-col space-y-2 p-4">
            @foreach($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    @click="close"
                    class="block py-2 hover:text-primary transition-colors {{ request()->routeIs($item['route']) ? 'text-primary' : '' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
            
            {{-- Mobile Language Switcher --}}
            <div class="py-2 border-t">
                <x-language-switcher class="w-full" />
            </div>
            
            <div class="flex flex-col space-y-2 pt-4 border-t">
                @auth
                    <a href="{{ route('member.portal') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                        {{ __('frontend.nav.dashboard') }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                            {{ __('frontend.nav.logout') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                        {{ __('frontend.nav.login') }}
                    </a>
                    <a href="{{ route('apply') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 rounded-md px-3">
                        {{ __('frontend.nav.apply_now') }}
                    </a>
                @endauth
            </div>
        </nav>
    </div>
</header>
