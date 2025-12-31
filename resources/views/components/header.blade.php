@props(['currentRoute' => null])

@php
    $navItems = [
        ['route' => 'home', 'label' => 'Home'],
        ['route' => 'about', 'label' => 'About'],
        ['route' => 'programs', 'label' => 'Programs'],
        ['route' => 'startups', 'label' => 'Startups'],
        ['route' => 'events', 'label' => 'Events'],
        ['route' => 'spaces', 'label' => 'Spaces'],
        ['route' => 'pricing', 'label' => 'Pricing'],
        ['route' => 'contact', 'label' => 'Contact'],
    ];
@endphp

<header class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur" x-data="mobileMenu">
    <div class="container flex h-16 items-center justify-between px-4 md:px-6">
        <a href="{{ route('home') }}" class="flex items-center space-x-2">
            <img src="{{ asset('theme/assets/images/logo.svg') }}" alt="Siliconile Logo" class="h-12 w-auto">
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden lg:flex items-center space-x-6 text-sm font-medium">
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

        <div class="hidden lg:flex items-center space-x-4">
            @auth
                <a wire:navigate href="{{ route('member.portal') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                    Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                        Logout
                    </button>
                </form>
            @else
                <a wire:navigate href="{{ route('login') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                    Login
                </a>
                <a wire:navigate href="{{ route('apply') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 rounded-md px-3">
                    Apply Now
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
            <div class="flex flex-col space-y-2 pt-4 border-t">
                @auth
                    <a href="{{ route('member.portal') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                        Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">
                        Login
                    </a>
                    <a href="{{ route('apply') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 rounded-md px-3">
                        Apply Now
                    </a>
                @endauth
            </div>
        </nav>
    </div>
</header>
