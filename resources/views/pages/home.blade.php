<?php
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};


new 
#[Layout('layouts.app')] 
#[Title('Siliconile | Empowering Egypt\'s Next Generation of Tech Startups')] 
class extends Component {

    


}

?>

{{-- <x-slot:title>Siliconile | Empowering Egypt's Next Generation of Tech Startups</x-slot:title> --}}
<main class="flex-1">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-primary/10 via-background to-secondary/20 py-20 md:py-32">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto text-center space-y-8">
                <div class="space-y-4">
                    <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold tracking-tight">
                        {{ __('frontend.hero.title_part1') }}
                        <span class="text-primary">{{ __('frontend.hero.title_highlight') }}</span>
                    </h1>
                    <p class="text-xl md:text-2xl text-muted-foreground max-w-3xl mx-auto">
                        {{ __('frontend.hero.subtitle') }}
                    </p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="{{ route('apply') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-lg font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-md px-8 py-6">
                        {{ __('frontend.hero.cta_primary') }}
                        <svg class="ms-2 rtl:rotate-180 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="{{ route('programs') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-lg font-medium ring-offset-background transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-11 rounded-md px-8 py-6">{{ __('frontend.hero.cta_secondary') }}</a>
                </div>

                <div class="flex flex-wrap justify-center gap-8 pt-8 text-sm text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>{{ __('frontend.hero.location') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>{{ __('frontend.hero.startups_supported', ['count' => 12]) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span>{{ __('frontend.hero.funding_raised', ['amount' => '2M']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-20 md:py-32">
        <div class="container px-4 md:px-6">
            <div class="text-center space-y-4 mb-16">
                <h2 class="text-3xl md:text-5xl font-bold">
                    {{ __('frontend.services.title_part1') }} <span class="text-primary">{{ __('frontend.services.title_highlight') }}</span>
                </h2>
                <p class="text-xl text-muted-foreground max-w-2xl mx-auto">
                    {{ __('frontend.services.subtitle') }}
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <!-- Service Cards -->
                <x-service-card 
                    icon="building" 
                    title="Coworking Spaces" 
                    description="Modern, fully-equipped offices and flexible workspaces designed for productivity and collaboration."
                />
                
                <x-service-card 
                    icon="currency" 
                    title="Startup Funding" 
                    description="Access to seed funding, venture capital connections, and investor networks to fuel your growth."
                />
                
                <x-service-card 
                    icon="users" 
                    title="Technical Support" 
                    description="Expert technical teams to help you build, scale, and optimize your technology stack."
                />
                
                <x-service-card 
                    icon="book" 
                    title="Workshops & Courses" 
                    description="Comprehensive training programs covering business development, marketing, and technical skills."
                />
                
                <x-service-card 
                    icon="lightbulb" 
                    title="Mentorship Program" 
                    description="One-on-one guidance from successful entrepreneurs and industry experts in Egypt and beyond."
                />
                
                <x-service-card 
                    icon="lightning" 
                    title="Business Development" 
                    description="Strategic planning, market analysis, and go-to-market strategies to accelerate your growth."
                />
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="grid gap-8 md:grid-cols-3 text-center">
                <div class="space-y-2">
                    <div class="text-4xl md:text-6xl font-bold text-primary">12+</div>
                    <div class="text-lg font-semibold">Startups Incubated</div>
                    <div class="text-muted-foreground">Since 2020</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl md:text-6xl font-bold text-primary">2M+ EGP</div>
                    <div class="text-lg font-semibold">Funding Raised</div>
                    <div class="text-muted-foreground">By our portfolio companies</div>
                </div>
                <div class="space-y-2">
                    <div class="text-4xl md:text-6xl font-bold text-primary">85%</div>
                    <div class="text-lg font-semibold">Success Rate</div>
                    <div class="text-muted-foreground">Startups still operating</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 md:py-32">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto text-center space-y-8">
                <h2 class="text-3xl md:text-5xl font-bold">
                    Ready to Turn Your Idea Into Reality?
                </h2>
                <p class="text-xl text-muted-foreground">
                    Join Egypt's most innovative startup community and get the support you need to build the next big thing.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-lg font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-md px-8 py-6">
                        Start Your Application
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="{{ route('about') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-lg font-medium ring-offset-background transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-11 rounded-md px-8 py-6">Learn More About Us</a>
                </div>
            </div>
        </div>
    </section>
</main>