<?php
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};


new 
#[Layout('layouts.app')] 
#[Title('Siliconile | Programs & Support')] 
class extends Component {

    


}

?>
  <main class="flex-1">
    <section class="py-20 md:py-32 bg-gradient-to-br from-primary/5 to-secondary/10">
      <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
          <h1 class="text-4xl md:text-6xl font-bold tracking-tight">Our <span class="text-primary">Programs</span></h1>
          <p class="text-xl md:text-2xl text-muted-foreground">Comprehensive support programs designed to take your startup from idea to scale, with world-class resources and expert guidance every step of the way.</p>
        </div>
      </div>
    </section>
    <section class="py-20 md:py-32">
      <div class="container px-4 md:px-6">
        <div class="space-y-20">
          <div class="grid gap-8 lg:grid-cols-2 items-center">
            <div class="space-y-6">
              <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                  <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div class="text-primary font-semibold text-sm uppercase tracking-wide">Flagship Program</div>
              </div>
              <h2 class="text-3xl md:text-4xl font-bold">Startup Incubation Program</h2>
              <p class="text-lg text-muted-foreground">Our flagship 6-month intensive program for early-stage startups. Get everything you need to validate your idea, build your MVP, and secure your first customers.</p>
              <div class="space-y-2">
                <h3 class="text-xl font-semibold">What You Get:</h3>
                <ul class="space-y-2">
                  <li class="flex items-center space-x-3"><svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Dedicated office space for 6 months</span></li>
                  <li class="flex items-center space-x-3"><svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>$10,000 seed funding</span></li>
                  <li class="flex items-center space-x-3"><svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Weekly 1-on-1 mentorship sessions</span></li>
                  <li class="flex items-center space-x-3"><svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Demo Day presentation to investors</span></li>
                </ul>
              </div>
              <a href="contact.html" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-md px-8">Apply for Incubation</a>
            </div>
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-8 bg-gradient-to-br from-primary/5 to-secondary/10">
              <h3 class="text-2xl font-bold mb-4">Success Stories</h3>
              <div class="space-y-4">
                <div><div class="font-semibold">TechFlow Solutions</div><div class="text-sm text-muted-foreground">Graduated 2023 • Raised $500K Series A</div></div>
                <div><div class="font-semibold">EduTech Egypt</div><div class="text-sm text-muted-foreground">Graduated 2022 • Serving 10K+ students</div></div>
                <div><div class="font-semibold">AgriSmart</div><div class="text-sm text-muted-foreground">Winner of Egypt Startup Awards 2024</div></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>  
</main>
