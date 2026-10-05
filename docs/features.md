# Holvek for Laravel - Capabilities Implemented

---

## 1. Content & Metadata Tools

- **On-Page SEO Checklist:** Analyzes your content, checking title length, meta description length, keyword usage, headings, and image alt text.
- **Readability Checker:** Measures how easy your text is to read using the Flesch-Kincaid scale. Highlights long sentences and excessive passive voice.
- **Search Result Preview:** Shows an accurate preview of how your page will look in Google, Bing, and other supported search results on both desktop and mobile screens.
- **Social Media Previews:** Shows how your post will look when shared on supported social media platforms like Facebook, X (Twitter), LinkedIn, Instagram, and TikTok.
- **Custom Canonical URLs:** Allows you to set a custom canonical URL to prevent duplicate content issues if not done automatically by the package.
- **Robots Meta Controls:** Set individual pages to `noindex`, `nofollow`, `noarchive`, `nosnippet`, or `noimageindex` directly from the dashboard or via Eloquent models.
- **FAQ Schema Builder:** Add questions and answers directly to automatically create structured FAQ schema for rich search results.
- **AI Writing Assistant (v1):** Suggests SEO titles and meta descriptions using your connected AI key (OpenAI, Anthropic, Ollama, or the Holvek remote API).
- **Internal Link Suggestions:** Suggests relevant articles on your site that you can link to.
- **Cornerstone Content:** Flag your most important articles. The system notifies you if these articles have not been updated in over 180 days.

---

## 2. Structured Data & Schema Markup

Holvek automatically adds valid Schema.org JSON-LD code to your pages so search engines can better understand your content.

Supported schema types include:
- **Article & Blog Post:** Includes author, publication date, last modified date, and featured image.
- **News Article:** Formatted specifically for Google News standards.
- **WebPage & WebSite:** Includes site search box markup.
- **Breadcrumb Navigation:** Displays clear breadcrumb trails in search results.
- **FAQ Page:** Displays accordion-style answers in search snippets.
- **How-To:** Formats step-by-step instructions.
- **Local Business/Organization:** Adds business name, address, phone number, and operating hours.
- **Recipe:** Formats recipe ingredients, cooking time, and nutrition info.
- **Event:** Formats event dates, venue, and attendance mode.
- **Product:** Outputs price, currency, and stock availability schema.
- **Custom Schema:** Output any custom JSON-LD schema when you need specialized markup.

---

## 3. XML & HTML Sitemaps

- **XML Sitemap Index (`/sitemap.xml`):** Generates clean, search-engine-ready sitemaps with automatic chunking.
- **Google News Sitemap (`/sitemap-news.xml`):** Automatically creates a news sitemap containing articles published in the last 48 hours, following Google News technical guidelines.
- **Video Sitemap (`/video-sitemap.xml`):** Finds embedded videos and includes them in a dedicated video sitemap.
- **Sitemap Controls:** Choose which models or routes to include or exclude.

---

## 4. Instant Search Engine Indexing Submission
Get your website indexed by Google, Bing, ChatGPT, and other search/answer engines.
- **IndexNow Integration:** Instantly notifies Bing, Yandex, and Seznam whenever you publish, update, or delete content.
- **Indexing Dashboard:** View submission logs, daily quota usage, and response statuses.

---

## 5. Technical SEO & Server Tools

- **Redirect Manager:** Set up 301 (permanent), 302 (temporary), and 307 redirects with full support for regular expressions (Regex) via Laravel middleware.
- **404 Error Monitor:** Automatically logs missing pages that visitors or bots try to open. See the URL, hit count, referrer, and turn any 404 into a 301 redirect with one click.
- **Robots.txt Editor:** Holvek generates a dynamic robots.txt file for your application to guide crawlers.
- **Favicon & Icon Checker:** Checks that your site has the required icons for browser tabs, mobile bookmarks, and web apps.

---

## 6. AI Crawler Guide (`llms.txt`)

- **Dynamic `llms.txt` and `llms-full.txt`:** Generates standard text files at your domain root that guide AI models and web crawlers (like ChatGPT, Claude, and Perplexity) on how to understand and cite your site's content.
- **Customizable Summary:** Set a concise description of your website, list key pages, and define guidelines for AI scrapers.

---

## 7. Link Health & Broken Link Monitoring

- **Internal Link Index:** Maps all internal links across your site into an organized table to analyze site architecture.
- **Orphaned Content Finder:** Detects published content that has zero incoming internal links.
- **Broken Link Checker:** Scans your published content via Laravel queued jobs for broken internal and external links in small batches.

---

## 8. Analytics & Performance

- **Google Search Console Integration:** View search clicks, impressions, click-through rates (CTR), and average rankings right in your dashboard.
- **Keyword Cannibalization Alert:** Highlights queries where multiple pages on your site compete against each other for the same search term.
- **Google Analytics 4 (GA4) Reports:** View session trends, top landing pages, and traffic channels without leaving your dashboard.
- **PageSpeed Insights:** Check mobile and desktop performance scores and Core Web Vitals directly from your dashboard.
- **Keyword Rank Tracker:** Add target keywords to track their Google ranking position over time.

---

## 9. Media & Image SEO

- **Image Alt Text Audit:** Identifies all images that are missing alt text.
- **Image Schema:** Automatically includes image dimensions and metadata in structured data.

---

## 10. Privacy & Compliance

- **Cookie Notice:** Built-in first-party notice that records a visitor choice. It is not a substitute for a consent-management platform.
- **AI Content Disclosure:** Add clear notices when content is generated or assisted by artificial intelligence.

---

## 11. User Roles & Team Access

- **Granular SEO Permissions:** Control access using Laravel Authorization Gates and Policies.
- **Audit History Log:** Keeps a clear record of every change made to SEO titles, descriptions, and settings in the `holvek_change_logs` table.

---

## 12. Headless REST API

- Clean REST endpoints to read and write SEO data in headless setups (Next.js, Nuxt, Vue).

---

## 13. Social media sharing

Easily manage page looks and share content on popular social media platforms like Facebook, X (Twitter), LinkedIn, Instagram, TikTok.

---

## 14. Real-Time Monitoring & Reporting
Track SEO, accessibility, performance, and best-practice scores as you work. Every change is measured instantly, helping you tune your site with absolute clarity.
* Live score updates
* Performance metrics
* Accessibility checks
* Best practices auditing

### Client Reporting
Generate branded, white-label reports with executive summaries and actionable recommendations.

### Technical SEO Analysis
23 metadata issues, 38 Core Web Vitals, content quality, structured data validation (JSON-LD, Microdata, RDFa), link analysis, security headers, mobile optimization, and i18n.

#### Core Web Vitals: 
* Largest Contentful Paint (LCP)
* Interaction to Next Paint (INP)
* Cumulative Layout Shift (CLS)

### Multiple Output Formats
JSON, JSONL, XML, CSV, HTML folder with interactive navigation, and individual JSON files per page. Fully customizable HTML reports with your branding.

### Discovery Check - /scan
Free Holvek Discovery Scan when run from any supported client provides:
* Basic visibility snapshot
* Technical discovery score
* AI mention check
* Google presence
* Entity consistency
* Competitor comparison
* Top opportunities