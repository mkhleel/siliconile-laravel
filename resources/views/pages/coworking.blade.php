<?php
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};


new 
#[Layout('layouts.app')] 
#[Title('Siliconile | Coworking Space')] 
class extends Component {

    


}

?>
  <main class="flex-1">
    <section class="py-20 md:py-32 bg-gradient-to-br from-primary/5 to-secondary/10">
      <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
          <h1 class="text-4xl md:text-6xl font-bold tracking-tight">Book a <span class="text-primary">{{ __('Co-working space Space') }}</span></h1>
          <p class="text-xl md:text-2xl text-muted-foreground">{{ __('Book your spot at our vibrant co-working space. Modern facilities, high-speed internet, and a thriving community of entrepreneurs.') }}</p>
        </div>
      </div>
    </section>
    <section class="py-20 md:py-32">
      <div class="container px-4 md:px-6">
        <div class="grid gap-12 lg:grid-cols-2">
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 lg:p-8">
            <h2 class="text-2xl font-semibold mb-4">{{ __('Booking Form') }}</h2>
            <p class="text-sm text-muted-foreground mb-6">{{ __('Fill out the form below to book your spot.') }}</p>
            <form class="space-y-6">
              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <label for="name" class="text-sm font-medium">{{ __('Full Name *') }}</label>
                  <input type="text" id="name" name="name" placeholder="{{ __('Enter your full name') }}" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                </div>
                <div class="space-y-2">
                  <label for="email" class="text-sm font-medium">{{ __('Email Address *') }}</label>
                  <input type="email" id="email" name="email" placeholder="{{ __('Enter your email') }}" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                </div>
              </div>
              <div class="space-y-2">
                <label for="company" class="text-sm font-medium">{{ __('Company/Startup Name') }}</label>
                <input type="text" id="company" name="company" placeholder="{{ __('Enter your company name') }}" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
              </div>
              <div class="space-y-2">
                <label for="date" class="text-sm font-medium">{{ __('Preferred Date *') }}</label>
                <input type="date" id="date" name="date" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
              </div>
              <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 w-full">{{ __('Submit Booking Request') }}</button>
            </form>
          </div>
          <div class="space-y-8">
            <h2 class="text-2xl font-bold">{{ __('Why Choose Our Co-working space Space?') }}</h2>
            <div class="space-y-6">
              <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                  <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div><h3 class="font-semibold mb-1">{{ __('High-Speed Internet') }}</h3><p class="text-muted-foreground">{{ __('Reliable fiber connection to keep you productive') }}</p></div>
              </div>
              <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                  <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div><h3 class="font-semibold mb-1">{{ __('Vibrant Community') }}</h3><p class="text-muted-foreground">{{ __('Network with like-minded entrepreneurs') }}</p></div>
              </div>
              <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                  <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div><h3 class="font-semibold mb-1">{{ __('Flexible Membership') }}</h3><p class="text-muted-foreground">{{ __('Daily, weekly, or monthly options available') }}</p></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>  
  </main>
