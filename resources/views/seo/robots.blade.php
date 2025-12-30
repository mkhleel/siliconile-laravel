# robots.txt for {{ config('app.name') }}
User-agent: *
Disallow: /admin
Disallow: /storage/private
Disallow: /api/internal
Disallow: /vendor

# Allow public storage
Allow: /storage/public

# Sitemap
Sitemap: {{ config('app.url') }}/sitemap.xml

# Common crawlers
User-agent: Googlebot
Allow: /
Disallow: /admin

User-agent: Bingbot
Allow: /
Disallow: /admin
