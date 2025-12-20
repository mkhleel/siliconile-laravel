<?php
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};


new 
#[Layout('layouts.app')] 
#[Title('Siliconile | Empowering Egypt\'s Next Generation of Tech Startups')] 
class extends Component {

    


}

?>
  <main class="flex-1">
    <!-- Hero Section -->
    <section class="py-20 md:py-32 bg-gradient-to-br from-primary/5 to-secondary/10">
      <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
          <h1 class="text-4xl md:text-6xl font-bold tracking-tight">
            About <span class="text-primary">Siliconile</span>
          </h1>
          <p class="text-xl md:text-2xl text-muted-foreground">
            We're on a mission to transform Luxor into Egypt's leading tech innovation hub, 
            empowering entrepreneurs to build world-class startups.
          </p>
        </div>
      </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-20 md:py-32">
      <div class="container px-4 md:px-6">
        <div class="grid gap-12 lg:grid-cols-2 items-center">
          <div class="space-y-8">
            <div class="space-y-4">
              <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
              </div>
              <h2 class="text-3xl md:text-4xl font-bold">Our Mission</h2>
              <p class="text-lg text-muted-foreground">
                To democratize entrepreneurship in Egypt by providing world-class incubation services, 
                mentorship, and resources that enable innovative startups to scale globally while staying rooted in their community.
              </p>
            </div>
          </div>
          
          <div class="space-y-8">
            <div class="space-y-4">
              <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
              </div>
              <h2 class="text-3xl md:text-4xl font-bold">Our Vision</h2>
              <p class="text-lg text-muted-foreground">
                To establish Luxor as a recognized tech startup ecosystem that attracts talent, 
                investment, and innovation from across the Middle East and North Africa, 
                contributing to Egypt's digital transformation.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Values -->
    <section class="py-20 bg-muted/50">
      <div class="container px-4 md:px-6">
        <div class="text-center space-y-4 mb-16">
          <h2 class="text-3xl md:text-5xl font-bold">Our Core Values</h2>
          <p class="text-xl text-muted-foreground max-w-2xl mx-auto">
            The principles that guide everything we do at Siliconile
          </p>
        </div>

        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm text-center group hover:shadow-lg transition-all duration-300">
            <div class="flex flex-col space-y-1.5 p-6">
              <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-primary/20 transition-colors">
                <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
              </div>
              <h3 class="text-2xl font-semibold leading-none tracking-tight">Community First</h3>
            </div>
            <div class="p-6 pt-0">
              <p class="text-muted-foreground">
                Building a supportive ecosystem where entrepreneurs can thrive together.
              </p>
            </div>
          </div>

          <div class="rounded-lg border bg-card text-card-foreground shadow-sm text-center group hover:shadow-lg transition-all duration-300">
            <div class="flex flex-col space-y-1.5 p-6">
              <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-primary/20 transition-colors">
                <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
              </div>
              <h3 class="text-2xl font-semibold leading-none tracking-tight">Excellence</h3>
            </div>
            <div class="p-6 pt-0">
              <p class="text-muted-foreground">
                Striving for the highest standards in everything we deliver.
              </p>
            </div>
          </div>

          <div class="rounded-lg border bg-card text-card-foreground shadow-sm text-center group hover:shadow-lg transition-all duration-300">
            <div class="flex flex-col space-y-1.5 p-6">
              <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-primary/20 transition-colors">
                <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
              </div>
              <h3 class="text-2xl font-semibold leading-none tracking-tight">Innovation</h3>
            </div>
            <div class="p-6 pt-0">
              <p class="text-muted-foreground">
                Encouraging creative thinking and groundbreaking solutions.
              </p>
            </div>
          </div>

          <div class="rounded-lg border bg-card text-card-foreground shadow-sm text-center group hover:shadow-lg transition-all duration-300">
            <div class="flex flex-col space-y-1.5 p-6">
              <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-primary/20 transition-colors">
                <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
              </div>
              <h3 class="text-2xl font-semibold leading-none tracking-tight">Impact</h3>
            </div>
            <div class="p-6 pt-0">
              <p class="text-muted-foreground">
                Creating meaningful change in our community and beyond.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="py-20 md:py-32">
      <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
          <h2 class="text-3xl md:text-5xl font-bold">Join Our Journey</h2>
          <p class="text-xl text-muted-foreground">
            Be part of Luxor's tech revolution. Apply to our programs or visit our coworking space today.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="contact.html" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-lg font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-md px-8 py-6">
              Get Started
              <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </a>
            <a href="programs.html" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-lg font-medium ring-offset-background transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-11 rounded-md px-8 py-6">View Programs</a>
          </div>
        </div>
      </div>
    </section>
  </main>
