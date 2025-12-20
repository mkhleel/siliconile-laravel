<x-layouts.app>
    <x-slot:title>About Us - Siliconile</x-slot:title>
    <x-slot:description>Learn about Siliconile, Luxor's premier startup incubator empowering Egypt's next generation of tech entrepreneurs.</x-slot:description>
    
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-primary/10 via-background to-secondary/20 py-20 md:py-32">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto text-center space-y-8">
                <h1 class="text-4xl md:text-6xl font-bold tracking-tight">
                    Building Egypt's <span class="text-primary">Tech Future</span>
                </h1>
                <p class="text-xl md:text-2xl text-muted-foreground">
                    We're on a mission to transform Luxor into a thriving tech hub by supporting innovative startups and entrepreneurs.
                </p>
            </div>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-20 md:py-32">
        <div class="container px-4 md:px-6">
            <div class="max-w-3xl mx-auto space-y-12">
                <div class="space-y-4">
                    <h2 class="text-3xl font-bold">Our Story</h2>
                    <p class="text-lg text-muted-foreground">
                        Founded in 2020, Siliconile emerged from a vision to bridge the gap between Egypt's rich cultural heritage and its technological future. We recognized the untapped potential in Luxor and decided to create a space where innovation could flourish.
                    </p>
                    <p class="text-lg text-muted-foreground">
                        Today, we're proud to have supported over 12 startups, helped raise more than 2 million EGP in funding, and built a thriving community of entrepreneurs, mentors, and investors.
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-3xl font-bold">Our Mission</h2>
                    <p class="text-lg text-muted-foreground">
                        To empower Egyptian entrepreneurs with the resources, knowledge, and connections they need to build successful tech companies that create value for Egypt and the world.
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-3xl font-bold">What We Offer</h2>
                    <ul class="space-y-3 text-lg text-muted-foreground">
                        <li class="flex items-start gap-3">
                            <svg class="h-6 w-6 text-primary flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>State-of-the-art coworking spaces designed for collaboration and productivity</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="h-6 w-6 text-primary flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Access to funding opportunities and investor networks</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="h-6 w-6 text-primary flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Mentorship from successful entrepreneurs and industry experts</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="h-6 w-6 text-primary flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Technical support and development resources</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="h-6 w-6 text-primary flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Regular workshops, training programs, and networking events</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-muted/50">
        <div class="container px-4 md:px-6">
            <div class="max-w-4xl mx-auto text-center space-y-8">
                <h2 class="text-3xl md:text-5xl font-bold">
                    Join Our Community
                </h2>
                <p class="text-xl text-muted-foreground">
                    Whether you're an entrepreneur with an idea or an established startup looking to scale, we're here to help you succeed.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-lg font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-md px-8 py-6">
                        Get Started
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    <a href="{{ route('programs') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-lg font-medium ring-offset-background transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-11 rounded-md px-8 py-6">
                        View Programs
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
