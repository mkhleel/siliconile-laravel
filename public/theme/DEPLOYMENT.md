# Deployment Guide for Siliconile Static Theme

This guide provides instructions for deploying the Siliconile static HTML theme to various hosting platforms.

## Quick Start

The theme is ready to deploy as-is. No build process is required unless you want to modify the styles.

## Deployment Options

### Option 1: Netlify (Recommended)

1. **Via Drag & Drop:**
   - Go to [netlify.com](https://www.netlify.com)
   - Sign up or log in
   - Drag and drop the entire `theme` folder to the Netlify dashboard
   - Your site is live!

2. **Via Netlify CLI:**
   ```bash
   npm install -g netlify-cli
   cd theme
   netlify deploy --prod
   ```

### Option 2: Vercel

1. **Via Vercel CLI:**
   ```bash
   npm install -g vercel
   cd theme
   vercel --prod
   ```

2. **Via Vercel Dashboard:**
   - Go to [vercel.com](https://vercel.com)
   - Import the project
   - Deploy

### Option 3: GitHub Pages

1. **Push to GitHub:**
   ```bash
   # Already in the repository, just merge to main
   git checkout main
   git merge copilot/extract-and-rebuild-theme
   git push
   ```

2. **Enable GitHub Pages:**
   - Go to repository Settings → Pages
   - Select the `theme` folder as the source
   - Save

3. **Access your site:**
   - Visit `https://[username].github.io/[repository]/theme/`

### Option 4: AWS S3

1. **Create S3 bucket:**
   ```bash
   aws s3 mb s3://your-bucket-name
   ```

2. **Upload files:**
   ```bash
   cd theme
   aws s3 sync . s3://your-bucket-name --acl public-read
   ```

3. **Enable static website hosting:**
   - Go to S3 bucket properties
   - Enable static website hosting
   - Set index document to `index.html`

### Option 5: Local Testing

For local development and testing:

```bash
cd theme
python3 -m http.server 8000
```

Then visit `http://localhost:8000`

## Build Commands (Optional)

If you modify the styles in `src/app.css`:

```bash
cd theme
npm install
npm run build:css
```

For development with auto-rebuild:

```bash
npm run watch:css
```

## Custom Domain Setup

### For Netlify:
1. Go to Site settings → Domain management
2. Add custom domain
3. Follow DNS configuration instructions

### For GitHub Pages:
1. Add a `CNAME` file in the theme folder with your domain
2. Configure DNS with your domain provider

### For Vercel:
1. Go to Project settings → Domains
2. Add custom domain
3. Follow DNS configuration instructions

## Environment Variables

This is a static site and doesn't require environment variables. However, if you integrate with a backend service for forms:

- Contact form submission URL
- Newsletter API endpoint
- Analytics tracking ID

Add these in your hosting platform's environment settings.

## SSL/HTTPS

All recommended hosting platforms provide free SSL certificates:

- **Netlify**: Automatic with Let's Encrypt
- **Vercel**: Automatic
- **GitHub Pages**: Automatic for github.io domains
- **AWS S3**: Use CloudFront for SSL

## Performance Optimization

The theme is already optimized:

- ✅ Minified CSS (~17KB)
- ✅ SVG images (scalable and small)
- ✅ No external dependencies (except Alpine.js from CDN)
- ✅ Efficient Tailwind CSS

For further optimization:

1. **Enable CDN** (most hosting platforms include this)
2. **Enable gzip compression** (automatic on most platforms)
3. **Add caching headers** (configure in hosting settings)

## Monitoring

Recommended monitoring tools:

- Google Analytics (add script to all pages)
- Google Search Console (for SEO)
- Uptime monitoring (UptimeRobot, Pingdom)

## Backup

Always keep a backup of your theme:

```bash
# Create a backup
tar -czf siliconile-theme-backup-$(date +%Y%m%d).tar.gz theme/

# Store in safe location
```

## Troubleshooting

### Images not loading
- Check file paths are relative
- Verify assets folder structure
- Ensure case-sensitive filenames match

### Styles not applying
- Verify `styles.css` is loaded
- Check browser console for errors
- Clear browser cache

### Mobile menu not working
- Verify Alpine.js is loaded from CDN
- Check JavaScript console for errors
- Ensure `app.js` is loaded

## Security Checklist

- [x] No hardcoded secrets
- [x] No server-side code (static HTML only)
- [x] HTTPS enabled
- [x] Security headers configured (via hosting platform)
- [x] Content Security Policy (optional, configure via hosting)

## Updates and Maintenance

To update the theme:

1. Make changes to HTML/CSS files
2. Test locally
3. Run `npm run build:css` if styles changed
4. Commit and push changes
5. Redeploy (automatic on most platforms)

## Support

For issues or questions:
- Check the README.md
- Review PAGES.md for page structure
- Contact the development team

---

**Quick Deploy Checklist:**

- [ ] Choose hosting platform
- [ ] Upload theme folder
- [ ] Configure custom domain (if needed)
- [ ] Enable HTTPS
- [ ] Test all pages
- [ ] Set up monitoring
- [ ] Configure forms (if using backend)
- [ ] Add analytics
- [ ] Test on mobile devices

---

Last updated: December 19, 2025
