<?php
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};


new 
#[Layout('layouts.app')] 
#[Title('Siliconile | Events & Workshops')] 
class extends Component {

    


}

?>
  <main class="flex-1">
    <section class="py-20 md:py-32 bg-gradient-to-br from-primary/5 to-secondary/10">
      <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
          <h1 class="text-4xl md:text-6xl font-bold tracking-tight">Events & <span class="text-primary">Workshops</span></h1>
          <p class="text-xl md:text-2xl text-muted-foreground">Join our vibrant community events, workshops, and networking sessions. Connect with fellow entrepreneurs, learn from experts, and grow your startup.</p>
        </div>
      </div>
    </section>
    <section class="py-20 md:py-32">
      <div class="container px-4 md:px-6">
        <div class="text-center space-y-4 mb-16">
          <h2 class="text-3xl md:text-5xl font-bold">Upcoming Events</h2>
          <p class="text-xl text-muted-foreground max-w-2xl mx-auto">Don't miss these exciting opportunities to learn, network, and grow your startup</p>
        </div>
        <div class="grid gap-8 lg:grid-cols-2">
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm group hover:shadow-lg transition-all duration-300">
            <div class="flex flex-col space-y-1.5 p-6">
              <div class="text-primary font-semibold text-sm uppercase tracking-wide mb-2">Networking</div>
              <h3 class="text-2xl font-semibold leading-none tracking-tight group-hover:text-primary transition-colors">Startup Pitch Night</h3>
              <p class="text-sm text-muted-foreground">Monthly pitch event where startups present to investors and get feedback from the community.</p>
            </div>
            <div class="p-6 pt-0 space-y-3">
              <div class="flex items-center space-x-3 text-sm"><svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span>January 25, 2025</span></div>
              <div class="flex items-center space-x-3 text-sm"><svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span>6:00 PM - 9:00 PM</span></div>
              <div class="flex items-center space-x-3 text-sm"><svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><span>Siliconile Hub, Luxor</span></div>
            </div>
          </div>
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm group hover:shadow-lg transition-all duration-300">
            <div class="flex flex-col space-y-1.5 p-6">
              <div class="text-primary font-semibold text-sm uppercase tracking-wide mb-2">Workshop</div>
              <h3 class="text-2xl font-semibold leading-none tracking-tight group-hover:text-primary transition-colors">Fundraising Masterclass</h3>
              <p class="text-sm text-muted-foreground">Learn from successful founders and VCs about raising capital, crafting pitch decks, and investor relations.</p>
            </div>
            <div class="p-6 pt-0 space-y-3">
              <div class="flex items-center space-x-3 text-sm"><svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><span>February 2, 2025</span></div>
              <div class="flex items-center space-x-3 text-sm"><svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span>2:00 PM - 5:00 PM</span></div>
              <div class="flex items-center space-x-3 text-sm"><svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><span>Online + Hybrid</span></div>
            </div>
          </div>
        </div>
      </div>
    </section>  
</main>
