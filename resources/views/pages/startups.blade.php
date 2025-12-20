<?php
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};


new 
#[Layout('layouts.app')] 
#[Title('Siliconile | Startups & Portfolio')] 
class extends Component {

    


}

?>
  <main class="flex-1">
    <section class="py-20 md:py-32 bg-gradient-to-br from-primary/5 to-secondary/10">
      <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
          <h1 class="text-4xl md:text-6xl font-bold tracking-tight">Our <span class="text-primary">Portfolio</span></h1>
          <p class="text-xl md:text-2xl text-muted-foreground">Meet the innovative startups that are part of the Siliconile family. These companies are driving digital transformation across Egypt and the MENA region.</p>
        </div>
      </div>
    </section>
    <section class="py-20">
      <div class="container px-4 md:px-6">
        <div class="grid gap-8 md:grid-cols-4 text-center mb-16">
          <div class="space-y-2"><div class="text-4xl md:text-5xl font-bold text-primary">12+</div><div class="text-lg font-semibold">Portfolio Companies</div></div>
          <div class="space-y-2"><div class="text-4xl md:text-5xl font-bold text-primary">2M+ EGP</div><div class="text-lg font-semibold">Total Funding Raised</div></div>
          <div class="space-y-2"><div class="text-4xl md:text-5xl font-bold text-primary">200+</div><div class="text-lg font-semibold">Jobs Created</div></div>
          <div class="space-y-2"><div class="text-4xl md:text-5xl font-bold text-primary">85%</div><div class="text-lg font-semibold">Success Rate</div></div>
        </div>
      </div>
    </section>
    <section class="py-20 bg-muted/50">
      <div class="container px-4 md:px-6">
        <div class="text-center space-y-4 mb-16">
          <h2 class="text-3xl md:text-5xl font-bold">Featured Startups</h2>
          <p class="text-xl text-muted-foreground max-w-2xl mx-auto">Discover the innovative companies building the future of technology in Egypt</p>
        </div>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm group hover:shadow-lg transition-all duration-300">
            <div class="flex flex-col space-y-1.5 p-6">
              <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-lg font-bold text-primary mb-4">TF</div>
              <h3 class="text-2xl font-semibold leading-none tracking-tight group-hover:text-primary transition-colors">TechFlow Solutions</h3>
              <p class="text-sm text-muted-foreground">AI-powered workflow automation platform for SMEs</p>
            </div>
            <div class="p-6 pt-0 space-y-2"><div class="flex justify-between text-sm"><span class="text-muted-foreground">Stage:</span><span class="font-semibold">Series A</span></div><div class="flex justify-between text-sm"><span class="text-muted-foreground">Funding:</span><span class="font-semibold">$500K</span></div></div>
          </div>
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm group hover:shadow-lg transition-all duration-300">
            <div class="flex flex-col space-y-1.5 p-6">
              <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-lg font-bold text-primary mb-4">EE</div>
              <h3 class="text-2xl font-semibold leading-none tracking-tight group-hover:text-primary transition-colors">EduTech Egypt</h3>
              <p class="text-sm text-muted-foreground">Digital learning platform for Arabic-speaking students</p>
            </div>
            <div class="p-6 pt-0 space-y-2"><div class="flex justify-between text-sm"><span class="text-muted-foreground">Stage:</span><span class="font-semibold">Seed</span></div><div class="flex justify-between text-sm"><span class="text-muted-foreground">Funding:</span><span class="font-semibold">$250K</span></div></div>
          </div>
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm group hover:shadow-lg transition-all duration-300">
            <div class="flex flex-col space-y-1.5 p-6">
              <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center text-lg font-bold text-primary mb-4">AS</div>
              <h3 class="text-2xl font-semibold leading-none tracking-tight group-hover:text-primary transition-colors">AgriSmart</h3>
              <p class="text-sm text-muted-foreground">IoT solutions for precision agriculture in Egypt</p>
            </div>
            <div class="p-6 pt-0 space-y-2"><div class="flex justify-between text-sm"><span class="text-muted-foreground">Stage:</span><span class="font-semibold">Pre-Series A</span></div><div class="flex justify-between text-sm"><span class="text-muted-foreground">Funding:</span><span class="font-semibold">$150K</span></div></div>
          </div>
        </div>
      </div>
    </section>  </main>
