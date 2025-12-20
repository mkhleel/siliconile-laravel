@props(['currentRoute' => null])

<header class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur" x-data="mobileMenu">
    <div class="container flex h-16 items-center justify-between px-4 md:px-6">
        <a href="{{ route('home') }}" class="flex items-center space-x-2">
            <img src="{{ asset('theme/assets/images/logo.svg') }}" alt="Siliconile Logo" class="h-12 w-auto">
        </a>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center space-x-6 text-sm font-medium">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors {{ $currentRoute == 'home' ? 'text-primary' : '' }}">Home</a>
            <a href="{{ route('about') }}" class="hover:text-primary transition-colors {{ $currentRoute == 'about' ? 'text-primary' : '' }}">About</a>
            <a href="{{ route('programs') }}" class="hover:text-primary transition-colors {{ $currentRoute == 'programs' ? 'text-primary' : '' }}">Programs</a>
            <a href="{{ route('startups') }}" class="hover:text-primary transition-colors {{ $currentRoute == 'startups' ? 'text-primary' : '' }}">Startups</a>
            <a href="{{ route('events') }}" class="hover:text-primary transition-colors {{ $currentRoute == 'events' ? 'text-primary' : '' }}">Events</a>
            <a href="{{ route('coworking') }}" class="hover:text-primary transition-colors {{ $currentRoute == 'coworking' ? 'text-primary' : '' }}">Co-working space</a>
            <a href="{{ route('contact') }}" class="hover:text-primary transition-colors {{ $currentRoute == 'contact' ? 'text-primary' : '' }}">Contact</a>
        </nav>

        <div class="hidden md:flex items-center space-x-4">
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">Apply Now</a>
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 rounded-md px-3">Join Community</a>
        </div>

        <!-- Mobile menu button -->
        <button @click="toggle" class="md:hidden inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 w-10">
            <svg x-show="!isOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg x-show="isOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile Navigation -->
    <div x-show="isOpen" class="md:hidden border-t bg-background">
        <nav class="flex flex-col space-y-2 p-4">
            <a href="{{ route('home') }}" @click="close" class="block py-2 hover:text-primary transition-colors">Home</a>
            <a href="{{ route('about') }}" @click="close" class="block py-2 hover:text-primary transition-colors">About</a>
            <a href="{{ route('programs') }}" @click="close" class="block py-2 hover:text-primary transition-colors">Programs</a>
            <a href="{{ route('startups') }}" @click="close" class="block py-2 hover:text-primary transition-colors">Startups</a>
            <a href="{{ route('events') }}" @click="close" class="block py-2 hover:text-primary transition-colors">Events</a>
            <a href="{{ route('coworking') }}" @click="close" class="block py-2 hover:text-primary transition-colors">Co-working space</a>
            <a href="{{ route('contact') }}" @click="close" class="block py-2 hover:text-primary transition-colors">Contact</a>
            <div class="flex flex-col space-y-2 pt-4">
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 rounded-md px-3">Apply Now</a>
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 rounded-md px-3">Join Community</a>
            </div>
        </nav>
    </div>
</header>
