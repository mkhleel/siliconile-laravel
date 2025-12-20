# Laravel Blade Theme Conversion

This document explains the converted HTML theme structure and how to use the new Blade components.

## 📁 File Structure

```
resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php           # Main layout with {{ $slot }}
│   ├── components/
│   │   ├── header.blade.php        # Header/navigation component
│   │   ├── footer.blade.php        # Footer component
│   │   └── service-card.blade.php  # Reusable service card
│   └── home.blade.php              # Home page (converted from index.html)
├── css/
│   └── theme.css                   # Theme CSS (copied from public/theme)
└── js/
    └── theme.js                    # Theme JS (Alpine.js handlers)
```

## 🚀 Getting Started

### 1. Build Assets

First, build the Vite assets:

```bash
npm install
npm run build
# or for development
npm run dev
```

### 2. Add Routes

Add the theme routes to your `routes/web.php`. Example routes are provided in `routes/theme-routes-example.php`.

### 3. Use the Layout

Create a new page using the layout:

```blade
<x-layouts.app>
    <x-slot:title>Page Title</x-slot:title>
    
    <!-- Your page content here -->
    <section>
        <h1>Welcome</h1>
    </section>
</x-layouts.app>
```

## 🧩 Components

### Main Layout (`layouts/app.blade.php`)

The main layout file that wraps all pages. It includes:
- Dynamic title and meta tags
- Vite asset loading
- Header and footer components
- Alpine.js integration
- Support for custom styles and scripts via slots

**Props:**
- `$title` (optional): Page title
- `$description` (optional): Meta description
- `$keywords` (optional): Meta keywords
- `$slot`: Main content area
- `$styles` (optional): Additional CSS
- `$scripts` (optional): Additional JavaScript

**Example:**
```blade
<x-layouts.app>
    <x-slot:title>Custom Page Title</x-slot:title>
    <x-slot:description>Custom meta description</x-slot:description>
    
    <x-slot:styles>
        <style>
            .custom-class { color: red; }
        </style>
    </x-slot:styles>
    
    <!-- Page content -->
    <div>Your content</div>
    
    <x-slot:scripts>
        <script>
            console.log('Custom script');
        </script>
    </x-slot:scripts>
</x-layouts.app>
```

### Header Component (`components/header.blade.php`)

Navigation header with mobile menu support.

**Props:**
- `currentRoute` (optional): Current route name to highlight active link

**Example:**
```blade
<x-header currentRoute="home" />
```

In your layout or page:
```blade
<x-header :currentRoute="request()->route()->getName()" />
```

### Footer Component (`components/footer.blade.php`)

Footer with company info, links, and newsletter subscription.

**Props:**
- `companyName` (optional): Company name (default: "Siliconile")
- `year` (optional): Copyright year (default: current year)

**Example:**
```blade
<x-footer companyName="Your Company" :year="2025" />
```

### Service Card Component (`components/service-card.blade.php`)

Reusable card component for displaying services/features.

**Props:**
- `icon` (required): Icon name (building, currency, users, book, lightbulb, lightning)
- `title` (required): Card title
- `description` (required): Card description

**Example:**
```blade
<x-service-card 
    icon="building" 
    title="Coworking Spaces" 
    description="Modern, fully-equipped offices and flexible workspaces."
/>
```

## 🎨 Assets

### CSS
The theme uses Tailwind CSS v4 with custom design tokens. The compiled CSS is located at `resources/css/theme.css`.

### JavaScript
Alpine.js is used for interactive components like the mobile menu. The theme JavaScript is at `resources/js/theme.js`.

### Vite Configuration
Assets are compiled using Vite. The configuration includes:
```javascript
input: [
    'resources/css/app.css', 
    'resources/js/app.js',
    'resources/css/theme.css',  // Theme CSS
    'resources/js/theme.js'     // Theme JS
]
```

## 📝 Creating New Pages

### Option 1: Using the Layout Directly

```blade
{{-- resources/views/about.blade.php --}}
<x-layouts.app>
    <x-slot:title>About Us - Siliconile</x-slot:title>
    
    <section class="py-20">
        <div class="container">
            <h1 class="text-4xl font-bold">About Us</h1>
            <p>Your content here...</p>
        </div>
    </section>
</x-layouts.app>
```

### Option 2: Creating Page-Specific Components

```blade
{{-- resources/views/components/hero-section.blade.php --}}
@props(['title', 'subtitle'])

<section class="py-20 bg-gradient-to-br from-primary/10 via-background to-secondary/20">
    <div class="container">
        <h1 class="text-5xl font-bold">{{ $title }}</h1>
        <p class="text-xl text-muted-foreground">{{ $subtitle }}</p>
    </div>
</section>
```

Then use it:
```blade
<x-layouts.app>
    <x-hero-section 
        title="Welcome" 
        subtitle="This is a subtitle"
    />
</x-layouts.app>
```

## 🔧 Customization

### Updating Navigation Links

Edit `resources/views/components/header.blade.php` and update the route names and labels.

### Customizing Footer

Edit `resources/views/components/footer.blade.php` to update:
- Social media links
- Contact information
- Footer links and sections

### Adding New Service Card Icons

Edit `resources/views/components/service-card.blade.php` and add new SVG paths to the `$icons` array.

## 🌐 Route Names

Make sure these route names are defined in your `routes/web.php`:

- `home` - Home page
- `about` - About page
- `programs` - Programs page
- `startups` - Startups page
- `events` - Events page
- `coworking` - Co-working space page
- `contact` - Contact page
- `newsletter.subscribe` - Newsletter subscription (POST)

## 🎯 Best Practices

1. **Use Named Routes**: Always use `route('name')` instead of hardcoded URLs
2. **Pass Current Route**: Pass the current route to header for active state highlighting
3. **Use Slots for Custom Content**: Use `<x-slot:name>` for passing custom styles/scripts
4. **Component Props**: Use `@props()` directive for type-safe component properties
5. **Asset Loading**: Always use `@vite()` directive for asset loading
6. **Translations**: Consider using `__()` helper for multilingual support

## 📦 Assets Location

Theme assets from `public/theme/assets/` should be referenced as:
```blade
{{ asset('theme/assets/images/logo.svg') }}
{{ asset('theme/assets/images/favicon.svg') }}
```

## 🔄 Converting Additional HTML Pages

To convert other HTML pages from the theme:

1. Copy the content between `<main>` tags
2. Create a new Blade file in `resources/views/`
3. Wrap content with `<x-layouts.app>`
4. Replace static links with `{{ route('name') }}`
5. Extract reusable sections into components
6. Add route in `web.php`

Example:
```blade
{{-- resources/views/contact.blade.php --}}
<x-layouts.app>
    <x-slot:title>Contact Us - Siliconile</x-slot:title>
    
    <!-- Your converted HTML content -->
    
</x-layouts.app>
```

## ⚡ Performance Tips

1. Run `npm run build` for production
2. Use `php artisan optimize` to cache routes and views
3. Enable Laravel's view caching in production
4. Consider using lazy loading for images

## 🐛 Troubleshooting

**Assets not loading?**
- Run `npm run dev` or `npm run build`
- Clear Laravel cache: `php artisan cache:clear`
- Check Vite is running for development

**Components not found?**
- Components in `resources/views/components/` are auto-discovered
- Use `<x-component-name />` syntax
- Check file naming (kebab-case for multi-word components)

**Routes not working?**
- Ensure routes are defined in `web.php`
- Clear route cache: `php artisan route:clear`
- Check route names match those used in components

## 📚 Additional Resources

- [Laravel Blade Documentation](https://laravel.com/docs/blade)
- [Laravel Vite Documentation](https://laravel.com/docs/vite)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Tailwind CSS v4 Documentation](https://tailwindcss.com/)
