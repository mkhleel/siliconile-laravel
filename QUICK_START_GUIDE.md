# Quick Start Guide

## 🎯 Using the Converted Theme

### 1. Create a New Page

```blade
{{-- resources/views/my-page.blade.php --}}
<x-layouts.app>
    <x-slot:title>My Page Title</x-slot:title>
    
    <section class="py-20">
        <div class="container px-4 md:px-6">
            <h1 class="text-4xl font-bold">My Page</h1>
            <p>Content goes here...</p>
        </div>
    </section>
</x-layouts.app>
```

### 2. Add Route

```php
// routes/web.php
Route::get('/my-page', function () {
    return view('my-page');
})->name('my-page');
```

### 3. Build Assets

```bash
npm run dev
```

### 4. View in Browser

```
http://localhost:8000/my-page
```

## 📦 Component Usage

### Layout with All Options

```blade
<x-layouts.app>
    {{-- Meta Tags --}}
    <x-slot:title>Custom Page Title</x-slot:title>
    <x-slot:description>Page description for SEO</x-slot:description>
    <x-slot:keywords>keyword1, keyword2, keyword3</x-slot:keywords>
    
    {{-- Custom Styles --}}
    <x-slot:styles>
        <style>
            .my-custom-class {
                color: blue;
            }
        </style>
    </x-slot:styles>
    
    {{-- Main Content --}}
    <section class="py-20">
        <h1>Page Content</h1>
    </section>
    
    {{-- Custom Scripts --}}
    <x-slot:scripts>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                console.log('Page loaded');
            });
        </script>
    </x-slot:scripts>
</x-layouts.app>
```

### Header with Active Route

```blade
{{-- In your layout or directly in a view --}}
<x-header :currentRoute="request()->route()->getName()" />
```

### Footer with Custom Props

```blade
<x-footer companyName="My Company" :year="2025" />
```

### Service Cards

```blade
<div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
    <x-service-card 
        icon="building" 
        title="Office Space" 
        description="Premium office locations"
    />
    
    <x-service-card 
        icon="users" 
        title="Team Building" 
        description="Collaborative workspace"
    />
    
    <x-service-card 
        icon="lightning" 
        title="Fast Internet" 
        description="High-speed connectivity"
    />
</div>
```

### Available Icons

```blade
<x-service-card icon="building" ... />    {{-- Office/Building --}}
<x-service-card icon="currency" ... />    {{-- Money/Funding --}}
<x-service-card icon="users" ... />       {{-- Team/People --}}
<x-service-card icon="book" ... />        {{-- Education/Learning --}}
<x-service-card icon="lightbulb" ... />   {{-- Ideas/Innovation --}}
<x-service-card icon="lightning" ... />   {{-- Speed/Energy --}}
```

## 🎨 Common Patterns

### Hero Section

```blade
<section class="relative bg-gradient-to-br from-primary/10 via-background to-secondary/20 py-20 md:py-32">
    <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold tracking-tight">
                Your Heading <span class="text-primary">With Accent</span>
            </h1>
            <p class="text-xl md:text-2xl text-muted-foreground max-w-3xl mx-auto">
                Your subtitle or description text
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-md px-8 py-6">
                    Primary Button
                </a>
                <a href="#" class="inline-flex items-center justify-center border border-input bg-background hover:bg-accent h-11 rounded-md px-8 py-6">
                    Secondary Button
                </a>
            </div>
        </div>
    </div>
</section>
```

### Content Section

```blade
<section class="py-20 md:py-32">
    <div class="container px-4 md:px-6">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold mb-8">Section Title</h2>
            <p class="text-lg text-muted-foreground mb-6">
                Your content paragraph...
            </p>
        </div>
    </div>
</section>
```

### CTA Section

```blade
<section class="py-20 bg-muted/50">
    <div class="container px-4 md:px-6">
        <div class="max-w-4xl mx-auto text-center space-y-8">
            <h2 class="text-3xl md:text-5xl font-bold">
                Call to Action Title
            </h2>
            <p class="text-xl text-muted-foreground">
                Supporting text for your CTA
            </p>
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-md px-8 py-6">
                Take Action
            </a>
        </div>
    </div>
</section>
```

## 🔗 Working with Routes

### In Blade Templates

```blade
{{-- Named routes (recommended) --}}
<a href="{{ route('home') }}">Home</a>
<a href="{{ route('about') }}">About</a>
<a href="{{ route('contact') }}">Contact</a>

{{-- With parameters --}}
<a href="{{ route('startup.show', $startup->id) }}">View Startup</a>

{{-- Current route check --}}
@if(request()->routeIs('home'))
    <span class="text-primary">You're on home!</span>
@endif
```

### Define Routes

```php
// routes/web.php

// Simple closure routes
Route::get('/', fn() => view('home'))->name('home');
Route::get('/about', fn() => view('about'))->name('about');

// Controller routes (recommended for complex logic)
Route::get('/startups', [StartupController::class, 'index'])->name('startups');
Route::get('/startups/{id}', [StartupController::class, 'show'])->name('startup.show');

// Route groups
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', fn() => view('admin.dashboard'))->name('admin.dashboard');
});
```

## 💡 Tips & Tricks

### 1. Passing Data to Views

```php
Route::get('/startups', function () {
    $startups = Startup::all();
    return view('startups', ['startups' => $startups]);
});
```

```blade
<x-layouts.app>
    @foreach($startups as $startup)
        <div>{{ $startup->name }}</div>
    @endforeach
</x-layouts.app>
```

### 2. Creating Reusable Sections

```blade
{{-- resources/views/components/stat-card.blade.php --}}
@props(['value', 'label', 'sublabel'])

<div class="space-y-2 text-center">
    <div class="text-4xl md:text-6xl font-bold text-primary">{{ $value }}</div>
    <div class="text-lg font-semibold">{{ $label }}</div>
    <div class="text-muted-foreground">{{ $sublabel }}</div>
</div>
```

Use it:
```blade
<x-stat-card value="12+" label="Startups" sublabel="Since 2020" />
```

### 3. Conditional Content

```blade
<x-layouts.app>
    @auth
        <p>Welcome back, {{ auth()->user()->name }}!</p>
    @endauth
    
    @guest
        <a href="{{ route('login') }}">Login</a>
    @endguest
</x-layouts.app>
```

### 4. Including Partials

```blade
{{-- Instead of components, for simple includes --}}
@include('partials.alert')
@include('partials.breadcrumb', ['items' => $breadcrumbs])
```

## 🚨 Common Mistakes to Avoid

❌ **Don't** use hardcoded URLs:
```blade
<a href="/about">About</a>  <!-- Bad -->
```

✅ **Do** use named routes:
```blade
<a href="{{ route('about') }}">About</a>  <!-- Good -->
```

---

❌ **Don't** forget CSRF tokens in forms:
```blade
<form method="POST">  <!-- Bad -->
```

✅ **Do** include @csrf:
```blade
<form method="POST">
    @csrf  <!-- Good -->
</form>
```

---

❌ **Don't** mix HTML and Blade syntax:
```blade
<a href="route('home')">  <!-- Bad -->
```

✅ **Do** use proper Blade syntax:
```blade
<a href="{{ route('home') }}">  <!-- Good -->
```

## 📱 Responsive Classes

The theme uses Tailwind CSS responsive prefixes:

```blade
<div class="
    text-sm          {{-- Mobile (default) --}}
    md:text-base     {{-- Tablet (768px+) --}}
    lg:text-lg       {{-- Desktop (1024px+) --}}
">
    Responsive text
</div>
```

Common breakpoints:
- `sm:` - 640px and up
- `md:` - 768px and up
- `lg:` - 1024px and up

## ✅ Checklist for New Pages

- [ ] Created Blade file in `resources/views/`
- [ ] Wrapped content with `<x-layouts.app>`
- [ ] Added `<x-slot:title>` for page title
- [ ] Replaced static links with `{{ route() }}`
- [ ] Added route in `routes/web.php`
- [ ] Tested on mobile and desktop
- [ ] Built assets with `npm run build` for production

## 🎓 Learn More

- [Laravel Blade Docs](https://laravel.com/docs/blade)
- [Tailwind CSS Docs](https://tailwindcss.com/)
- [Alpine.js Docs](https://alpinejs.dev/)

---

**You're all set! Start building amazing pages! 🚀**
