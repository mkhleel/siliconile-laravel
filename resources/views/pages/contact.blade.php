<?php
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};


new 
#[Layout('layouts.app')] 
#[Title('Siliconile | Contact Us')] 
class extends Component {

    


}

?>
  <main class="flex-1">
    <section class="py-20 md:py-32 bg-gradient-to-br from-primary/5 to-secondary/10">
      <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
          <h1 class="text-4xl md:text-6xl font-bold tracking-tight">Get in <span class="text-primary">Touch</span></h1>
          <p class="text-xl md:text-2xl text-muted-foreground">Ready to start your entrepreneurial journey? We'd love to hear from you. Let's discuss how Siliconile can help turn your idea into reality.</p>
        </div>
      </div>
    </section>
    <section class="py-20 md:py-32">
      <div class="container px-4 md:px-6">
        <div class="grid gap-12 lg:grid-cols-2">
          <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 lg:p-8">
            <h2 class="text-2xl font-semibold mb-4">Send us a Message</h2>
            <p class="text-sm text-muted-foreground mb-6">Fill out the form below and we'll get back to you within 24 hours.</p>
            <form class="space-y-6">
              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <label for="name" class="text-sm font-medium">Full Name *</label>
                  <input type="text" id="name" name="name" placeholder="Enter your full name" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                </div>
                <div class="space-y-2">
                  <label for="email" class="text-sm font-medium">Email Address *</label>
                  <input type="email" id="email" name="email" placeholder="Enter your email" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                </div>
              </div>
              <div class="space-y-2">
                <label for="company" class="text-sm font-medium">Company/Startup Name</label>
                <input type="text" id="company" name="company" placeholder="Enter your company name" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
              </div>
              <div class="space-y-2">
                <label for="inquiryType" class="text-sm font-medium">Inquiry Type *</label>
                <select id="inquiryType" name="inquiryType" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                  <option value="general">General Inquiry</option>
                  <option value="incubation">Incubation Program</option>
                  <option value="coworking">Coworking Space</option>
                  <option value="partnership">Partnership</option>
                  <option value="media">Media Inquiry</option>
                </select>
              </div>
              <div class="space-y-2">
                <label for="message" class="text-sm font-medium">Message *</label>
                <textarea id="message" name="message" placeholder="Tell us about your project or inquiry..." required rows="6" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
              </div>
              <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 w-full">
                Send Message
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
              </button>
            </form>
          </div>
          <div class="space-y-8">
            <div>
              <h2 class="text-2xl font-bold mb-6">Contact Information</h2>
              <div class="space-y-6">
                <div class="flex items-start space-x-4">
                  <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                  </div>
                  <div>
                    <h3 class="font-semibold mb-1">Address</h3>
                    <p class="text-muted-foreground">Luxor, Egypt</p>
                  </div>
                </div>
                <div class="flex items-start space-x-4">
                  <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                  </div>
                  <div>
                    <h3 class="font-semibold mb-1">Email</h3>
                    <p class="text-muted-foreground">info@siliconile.com</p>
                  </div>
                </div>
                <div class="flex items-start space-x-4">
                  <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                  </div>
                  <div>
                    <h3 class="font-semibold mb-1">Phone</h3>
                    <p class="text-muted-foreground">+20 111 765 0004</p>
                  </div>
                </div>
                <div class="flex items-start space-x-4">
                  <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  </div>
                  <div>
                    <h3 class="font-semibold mb-1">Office Hours</h3>
                    <p class="text-muted-foreground">Sunday - Thursday: 9:00 AM - 6:00 PM</p>
                    <p class="text-muted-foreground">Friday & Saturday: Closed</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
