# WatchNexus SEO Implementation Guide

**Goal:** Get WatchNexus ranked on Google for TV tracking, show calendar, and TV organization searches

---

## 🎯 TARGET KEYWORDS

### **Primary Keywords:**
- TV show tracker
- TV calendar app
- Track TV shows
- TV show organizer
- What to watch tonight
- TV episode tracker
- Show release calendar

### **Long-tail Keywords:**
- best TV show tracker 2026
- free TV show calendar
- track my TV shows online
- when is my show airing
- upcoming TV episodes calendar
- organize TV watchlist
- TV show reminder app

### **Location Keywords (if targeting Canada):**
- TV show tracker Canada
- Canadian TV calendar
- track TV shows in Canada

---

## 📄 META TAGS (Add to layout.php <head>)

```html
<!-- Primary Meta Tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WatchNexus - Track TV Shows, Episodes & Air Dates | Free TV Calendar</title>
<meta name="title" content="WatchNexus - Track TV Shows, Episodes & Air Dates | Free TV Calendar">
<meta name="description" content="Never miss an episode! WatchNexus helps you track TV shows, view upcoming episodes, and organize your watchlist. Free TV calendar with 50,000+ shows.">
<meta name="keywords" content="TV show tracker, TV calendar, track episodes, TV organizer, show release dates, upcoming episodes, TV watchlist, episode tracker">
<meta name="author" content="WatchNexus">
<meta name="robots" content="index, follow">
<meta name="language" content="English">
<meta name="revisit-after" content="7 days">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://watchnexus.ca/">
<meta property="og:title" content="WatchNexus - Track TV Shows & Never Miss an Episode">
<meta property="og:description" content="Free TV show tracker with calendar, episode alerts, and 50,000+ shows. Track what you watch, discover new series, and stay updated.">
<meta property="og:image" content="https://watchnexus.ca/assets/og-image.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="https://watchnexus.ca/">
<meta property="twitter:title" content="WatchNexus - Track TV Shows & Never Miss an Episode">
<meta property="twitter:description" content="Free TV show tracker with calendar, episode alerts, and 50,000+ shows. Track what you watch, discover new series, and stay updated.">
<meta property="twitter:image" content="https://watchnexus.ca/assets/twitter-image.jpg">

<!-- Favicon -->
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">

<!-- Canonical URL -->
<link rel="canonical" href="https://watchnexus.ca/">

<!-- Structured Data (JSON-LD) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "WatchNexus",
  "url": "https://watchnexus.ca",
  "logo": "https://watchnexus.ca/assets/logo.png",
  "description": "Track TV shows, view upcoming episodes, and organize your watchlist with WatchNexus - the free TV show tracker.",
  "applicationCategory": "Entertainment",
  "operatingSystem": "Web Browser",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "USD"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "ratingCount": "1250"
  }
}
</script>
```

---

## 🗺️ SITEMAP.XML

Create `public/sitemap.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://watchnexus.ca/</loc>
    <lastmod>2026-01-11</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://watchnexus.ca/?page=calendar</loc>
    <lastmod>2026-01-11</lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc>https://watchnexus.ca/?page=browse</loc>
    <lastmod>2026-01-11</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>https://watchnexus.ca/?page=register</loc>
    <lastmod>2026-01-11</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc>https://watchnexus.ca/?page=privacy</loc>
    <lastmod>2026-01-11</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
  <url>
    <loc>https://watchnexus.ca/?page=terms</loc>
    <lastmod>2026-01-11</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>
</urlset>
```

---

## 🤖 ROBOTS.TXT

Create `public/robots.txt`:

```
User-agent: *
Allow: /
Disallow: /api/
Disallow: /admin/
Disallow: /*?action=
Disallow: /*auth=

Sitemap: https://watchnexus.ca/sitemap.xml
```

---

## 📝 LANDING PAGE CONTENT (Add to homepage)

```html
<section class="hero">
  <h1>Never Miss Another Episode</h1>
  <p class="subtitle">Track 50,000+ TV shows, view upcoming episodes, and organize your watchlist—all in one place.</p>
  <div class="cta-buttons">
    <a href="?page=register" class="btn primary">Start Tracking Free</a>
    <a href="?page=browse" class="btn secondary">Browse Shows</a>
  </div>
</section>

<section class="features">
  <h2>Why Choose WatchNexus?</h2>
  
  <div class="feature-grid">
    <div class="feature-card">
      <div class="icon">📅</div>
      <h3>TV Calendar</h3>
      <p>See all your shows in one calendar. Never forget when your favorite series airs.</p>
    </div>
    
    <div class="feature-card">
      <div class="icon">📺</div>
      <h3>Track Shows</h3>
      <p>Mark shows you're watching. Get organized and see what's coming next.</p>
    </div>
    
    <div class="feature-card">
      <div class="icon">🔍</div>
      <h3>Discover New Series</h3>
      <p>Browse 50,000+ TV shows. Find your next binge-worthy series.</p>
    </div>
    
    <div class="feature-card">
      <div class="icon">🎨</div>
      <h3>Beautiful Themes</h3>
      <p>5 stunning themes to match your style. Dark mode included.</p>
    </div>
    
    <div class="feature-card">
      <div class="icon">🔗</div>
      <h3>Quick Links</h3>
      <p>Jump to IMDb, Wikipedia, or streaming services instantly.</p>
    </div>
    
    <div class="feature-card">
      <div class="icon">🆓</div>
      <h3>100% Free</h3>
      <p>No ads, no paywalls, no credit card required. Just track your shows.</p>
    </div>
  </div>
</section>

<section class="how-it-works">
  <h2>How It Works</h2>
  <ol class="steps">
    <li>
      <strong>Sign Up Free</strong>
      <p>Create your account in 30 seconds.</p>
    </li>
    <li>
      <strong>Add Your Shows</strong>
      <p>Search and track shows you're watching.</p>
    </li>
    <li>
      <strong>Check Calendar</strong>
      <p>See upcoming episodes at a glance.</p>
    </li>
    <li>
      <strong>Never Miss an Episode</strong>
      <p>Stay updated with your personal TV schedule.</p>
    </li>
  </ol>
</section>

<section class="cta-final">
  <h2>Ready to Get Organized?</h2>
  <p>Join thousands of TV fans tracking their shows with WatchNexus.</p>
  <a href="?page=register" class="btn primary large">Start Tracking Free →</a>
</section>
```

---

## 🔗 BACKLINK STRATEGY

### **1. Submit to Directories:**
- Product Hunt
- AlternativeTo
- Slant.co
- Capterra (if applicable)
- SourceForge (if open source)

### **2. Reddit Presence:**
- r/television
- r/cordcutters
- r/trackers (be careful - focus on tracking, not piracy)
- r/selfhosted (if you make it self-hostable)
- r/webdev (showcase as a project)

### **3. Community Engagement:**
- TV forums (AVForums, TV.com forums)
- Answer questions on Quora about "best TV trackers"
- Stack Exchange (Software Recommendations)

### **4. Content Marketing:**
- Blog posts:
  - "How to Track TV Shows in 2026"
  - "Best Free TV Calendar Apps"
  - "Organize Your TV Watchlist"
- Guest posts on tech blogs
- Comparison articles (vs Trakt, vs TV Time)

---

## 📊 GOOGLE SEARCH CONSOLE SETUP

1. **Verify Your Site:**
   - Go to https://search.google.com/search-console
   - Add property: `https://watchnexus.ca`
   - Verify via HTML file or DNS

2. **Submit Sitemap:**
   - In Search Console → Sitemaps
   - Submit: `https://watchnexus.ca/sitemap.xml`

3. **Monitor:**
   - Check "Performance" tab weekly
   - Fix any "Coverage" errors
   - Track keyword rankings

---

## 🚀 PAGE SPEED OPTIMIZATION

```html
<!-- Preconnect to external domains -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://cdnjs.cloudflare.com">

<!-- Preload critical resources -->
<link rel="preload" href="/assets/css/base.css" as="style">
<link rel="preload" href="/assets/js/app.js" as="script">
```

**Compress images:**
```bash
# Use TinyPNG or ImageOptim
# Target: <100KB per image
```

**Minify CSS/JS:**
```bash
# Use online tools or build process
# Combine multiple files when possible
```

---

## 📈 ANALYTICS TRACKING

**Add Google Analytics (GA4):**

```html
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
</script>
```

**Track Key Events:**
- Registration conversions
- Show tracking (add to My Shows)
- Calendar views
- Browse searches

---

## 🎯 LOCAL SEO (If Targeting Canada)

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "WatchNexus",
  "areaServed": {
    "@type": "Country",
    "name": "Canada"
  },
  "availableLanguage": ["en-CA"],
  "inLanguage": "en-CA"
}
</script>
```

---

## 📱 MOBILE OPTIMIZATION

```html
<!-- Ensure these are in <head> -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="WatchNexus">
```

---

## 🔍 RICH SNIPPETS

**Add to show detail pages (future):**

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TVSeries",
  "name": "Breaking Bad",
  "description": "A high school chemistry teacher turned meth manufacturer...",
  "image": "https://watchnexus.ca/posters/breaking-bad.jpg",
  "genre": ["Crime", "Drama", "Thriller"],
  "numberOfEpisodes": 62,
  "numberOfSeasons": 5
}
</script>
```

---

## ✅ SEO CHECKLIST

- [ ] Meta tags in layout.php
- [ ] sitemap.xml created
- [ ] robots.txt created
- [ ] Google Search Console verified
- [ ] Sitemap submitted to Google
- [ ] Analytics installed (GA4)
- [ ] Social media images (OG images) created
- [ ] Page speed tested (Google PageSpeed Insights)
- [ ] Mobile-friendly test passed
- [ ] SSL certificate active (HTTPS)
- [ ] Content-rich homepage added
- [ ] Internal linking structure clear
- [ ] Footer links to legal pages
- [ ] Unique meta descriptions for each page

---

## 🎯 EXPECTED TIMELINE

**Week 1-2:** Indexing begins (Google finds your site)
**Week 3-4:** First rankings appear (long-tail keywords)
**Month 2-3:** Keyword positions improve
**Month 4-6:** Steady traffic growth
**Month 6+:** Established rankings for main keywords

**Pro Tip:** SEO is a marathon, not a sprint. Keep adding content, fixing issues, and building links!

---

**Next:** Submit to directories, post on social media, and start content marketing!
