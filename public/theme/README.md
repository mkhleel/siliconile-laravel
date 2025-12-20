# Siliconile Static HTML Theme

This is a fully functional static HTML theme extracted from the Siliconile website. It includes all pages, styling with TailwindCSS, and interactivity with Alpine.js.

## 📁 Folder Structure

```
theme/
├── assets/
│   ├── css/
│   │   └── styles.css           # Compiled Tailwind CSS
│   ├── js/
│   │   └── app.js                # Alpine.js configuration
│   └── images/                   # All website assets (logos, icons, etc.)
├── src/
│   └── app.css                   # Tailwind CSS source with directives
├── index.html                    # Home page
├── about.html                    # About page
├── programs.html                 # Programs page
├── startups.html                 # Startups page
├── events.html                   # Events page
├── co-working.html               # Co-working space page
├── contact.html                  # Contact page
├── tailwind.config.js            # Tailwind configuration
├── package.json                  # NPM dependencies
└── README.md                     # This file
```

## 🚀 Features

- **7 Complete Pages**: Home, About, Programs, Startups, Events, Co-working space, and Contact
- **TailwindCSS Styling**: All original styles preserved using Tailwind utility classes
- **Alpine.js Interactivity**: Mobile menu toggle functionality
- **Fully Responsive**: Mobile-first design that works on all devices
- **Static HTML**: No build process required for deployment
- **Custom Color Scheme**: Matches the original website's brand colors
- **Reusable Components**: Header and footer are consistent across all pages

## 🎨 Design System

### Colors
The theme uses a custom color palette defined in CSS variables:
- **Primary**: Blue (#4A90E2) - Used for CTAs and accents
- **Secondary**: Gray tones for backgrounds
- **Muted**: Subdued colors for secondary text
- **Background**: White/dark backgrounds for sections

### Typography
- **Font Family**: System fonts for optimal performance
- **Font Sizes**: Responsive typography that scales from mobile to desktop

## 🛠️ Development

### Prerequisites
- Node.js (v14 or higher)
- npm or yarn

### Setup Instructions

1. **Navigate to the theme directory**:
   ```bash
   cd theme
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

3. **Build CSS** (if you modify src/app.css):
   ```bash
   npm run build:css
   ```

4. **Watch CSS** (for development):
   ```bash
   npm run watch:css
   ```

### Modifying Styles

The theme uses Tailwind CSS. To customize:

1. Edit `src/app.css` to modify CSS variables or add custom styles
2. Edit `tailwind.config.js` to change Tailwind configuration
3. Run `npm run build:css` to recompile

### Adding Interactivity

The theme uses Alpine.js for interactivity. The mobile menu is already implemented in `assets/js/app.js`. To add more interactive features:

1. Add Alpine.js directives to your HTML elements
2. Define Alpine.js components in `assets/js/app.js`

Example:
```html
<div x-data="{ open: false }">
  <button @click="open = !open">Toggle</button>
  <div x-show="open">Content</div>
</div>
```

## 📦 Deployment

The theme is ready for deployment to any static hosting service:

### Option 1: Simple HTTP Server
```bash
cd theme
python3 -m http.server 8000
```
Then visit `http://localhost:8000`

### Option 2: Deploy to Netlify
1. Create a new site on Netlify
2. Drag and drop the `theme` folder
3. Your site is live!

### Option 3: Deploy to GitHub Pages
1. Push the `theme` folder to a GitHub repository
2. Enable GitHub Pages in repository settings
3. Select the `theme` folder as the source

### Option 4: Deploy to Vercel
```bash
cd theme
vercel deploy
```

## 📝 Customization Guide

### Changing Colors
Edit the CSS variables in `src/app.css`:
```css
:root {
  --primary: 217 91% 60%;  /* Change this */
  --secondary: 240 4.8% 95.9%;
  /* ... */
}
```

### Updating Content
Simply edit the HTML files directly. All content is in plain HTML with Tailwind classes.

### Modifying Navigation
The navigation is in the `<header>` section of each page. Update all pages consistently or use a template system.

### Adding New Pages
1. Copy an existing HTML file (e.g., `about.html`)
2. Update the content within the `<main>` tag
3. Update the page title and meta description in `<head>`
4. Add the new page to the navigation menu in all existing pages

## 🌐 Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## 📄 License

This theme is extracted from the Siliconile website. Please ensure you have the appropriate rights to use it.

## 🤝 Support

For questions or issues related to this theme, please contact the development team or create an issue in the repository.

## 🔧 Technical Notes

### TailwindCSS v3
The theme uses Tailwind CSS v3 with the following features:
- JIT (Just-In-Time) mode for faster builds
- Custom color palette
- Responsive utilities
- Dark mode support (class-based)

### Alpine.js v3
Alpine.js is loaded from CDN for simplicity. The theme uses:
- `x-data` for component state
- `x-show` for conditional visibility
- `@click` for event handling
- `x-cloak` to prevent flash of unstyled content

### Asset Optimization
- SVG files for logos and icons (scalable and small)
- Minified CSS for production
- No JavaScript frameworks except Alpine.js (lightweight)

## 📊 Performance

The theme is optimized for performance:
- **Page Size**: ~30-50 KB per page (HTML)
- **CSS Size**: ~15 KB (minified)
- **JavaScript**: ~15 KB (Alpine.js from CDN)
- **Load Time**: < 1 second on 3G connection

## 🔄 Build Commands

Available npm scripts:

```bash
npm run build:css    # Build and minify CSS
npm run watch:css    # Watch CSS changes during development
```

---

**Built with ❤️ for Siliconile**
