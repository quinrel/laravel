import os

filepath = '/Applications/XAMPP/xamppfiles/htdocs/holvek-laravel/TASKS.md'
with open(filepath, 'r') as f:
    content = f.read()

# 1. Phase 2: SPA Adapters
old_p2 = """- [ ] **Robots Meta Controls:** UI to set `noindex`, `nofollow`, `noarchive`, `nosnippet`, `noimageindex` per page.
- [ ] **Aggressive Caching Layer:** Cache rendered HTML strings and invalidate via Observers."""

new_p2 = """- [ ] **Robots Meta Controls:** UI to set `noindex`, `nofollow`, `noarchive`, `nosnippet`, `noimageindex` per page.
- [ ] **Aggressive Caching Layer:** Cache rendered HTML strings and invalidate via Observers.
- [ ] **SPA Native Adapters (Inertia.js & Livewire):** 
  - *Note to prevent execution drift:* Standard Blade rendering fails on client-side SPA navigation. Build `Holvek::inertia()` and `Holvek::livewire()` middleware to automatically intercept and share `@holvekHead` state with the client-side router, preserving SEO metadata during AJAX navigations."""

content = content.replace(old_p2, new_p2)

# 2. Phase 4: Sitemap Streaming & Markdown Endpoints
old_p4 = """- [ ] Build the core sitemap generator with automatic monthly chunking (`sitemap.xml`).
- [ ] **News Sitemap:** Generator for `sitemap-news.xml` (last 48 hours).
- [ ] **Video Sitemap:** Generator for `video-sitemap.xml` (extracting embedded videos).
- [ ] **HTML Sitemap:** Create `<x-holvek-sitemap />` Blade component for user-facing navigation.
- [ ] **Robots.txt Editor:** Allow safe custom document replacement.
- [ ] **.htaccess Editor:** Optional tool for Apache users to manage redirects/rules.
- [ ] **AI Crawler Guide:** Generate dynamic `llms.txt` and `llms-full.txt` with customizable summaries."""

new_p4 = """- [ ] Build the core sitemap generator with automatic monthly chunking (`sitemap.xml`).
  - [ ] **Memory-Safe Sitemap Streaming:** *Note to prevent execution drift:* Do not load models into RAM. Use PHP Generators (`yield`) and unbuffered Eloquent queries (`lazy()`) to stream XML directly to disk/S3. This prevents fatal memory exhaustion on datasets exceeding 100,000+ URLs.
- [ ] **News Sitemap:** Generator for `sitemap-news.xml` (last 48 hours).
- [ ] **Video Sitemap:** Generator for `video-sitemap.xml` (extracting embedded videos).
- [ ] **HTML Sitemap:** Create `<x-holvek-sitemap />` Blade component for user-facing navigation.
- [ ] **Robots.txt Editor:** Allow safe custom document replacement.
- [ ] **.htaccess Editor:** Optional tool for Apache users to manage redirects/rules.
- [ ] **AI Crawler Guide:** Generate dynamic `llms.txt` and `llms-full.txt` with customizable summaries.
- [ ] **AEO Markdown Endpoints (`.md` / `.llm`):** 
  - *Note to prevent execution drift:* AI scrapers (ChatGPT, Perplexity) hallucinate when parsing heavy HTML layouts. Implement a route suffix (e.g., `/{slug}.md`) or Content-Negotiation layer that bypasses the Blade layout and returns pure, clean Markdown text to ensure accurate AI ingestion."""

content = content.replace(old_p4, new_p4)

# 3. Phase 5: E-E-A-T Schema
old_p5 = """- [ ] Implement the Schema Generator service.
- [ ] Support core schemas: **Article, NewsArticle, WebPage, WebSite, Breadcrumb, FAQPage, How-To, LocalBusiness, Recipe, Event, Product**.
- [ ] **Custom Schema:** Allow pasting custom JSON-LD directly into the UI."""

new_p5 = """- [ ] Implement the Schema Generator service.
- [ ] Support core schemas: **Article, NewsArticle, WebPage, WebSite, Breadcrumb, FAQPage, How-To, LocalBusiness, Recipe, Event, Product**.
- [ ] **E-E-A-T Schema Automation (Experience, Expertise, Authoritativeness, Trust):** 
  - *Note to prevent execution drift:* Standard Article schema is insufficient against AI-content ranking penalties. Automate deeply nested `Person` (Author) schema linking to `Organization`, injecting `sameAs` social links and bio credentials directly into the JSON-LD payload to strongly signal trust to algorithms.
- [ ] **Custom Schema:** Allow pasting custom JSON-LD directly into the UI."""

content = content.replace(old_p5, new_p5)

# 4. Phase 10: Algorithm Overlays
old_p10 = """- [ ] **Daily URL Monitoring:** Scheduled cron to track daily indexing and HTTP status changes for critical URLs.
- [ ] **GSC Analytics:** Fetch GSC metrics (Clicks, Impressions, CTR) natively.
- [ ] **GSC Index Status:** View exact index status using the URL Inspection API."""

new_p10 = """- [ ] **Daily URL Monitoring:** Scheduled cron to track daily indexing and HTTP status changes for critical URLs.
- [ ] **GSC Analytics:** Fetch GSC metrics (Clicks, Impressions, CTR) natively.
  - [ ] **Google Algorithm Update Overlays:** *Note to prevent execution drift:* Overlay known Google Core Algorithm update dates as vertical markers directly on the GSC traffic charts. This allows users to instantly visually correlate traffic drops with search engine algorithm shifts.
- [ ] **GSC Index Status:** View exact index status using the URL Inspection API."""

content = content.replace(old_p10, new_p10)

with open(filepath, 'w') as f:
    f.write(content)

print("Tasks Updated Successfully")
