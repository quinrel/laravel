import os

filepath = '/Applications/XAMPP/xamppfiles/htdocs/holvek-laravel/TASKS.md'
with open(filepath, 'r') as f:
    content = f.read()

# Revert Phase 6
old_p6 = """## Phase 6: Image SEO & Media 🖼️
**Goal:** Optimize images for discoverability and accessibility.
- [ ] **Image Alt Text Audit:** Identify missing alt text and provide quick-add links.
- [ ] **Clean Filename Enforcer:** Auto-rename uploads to lowercase, hyphen-separated filenames.
- [ ] **Image Schema:** Automatically include image dimensions and metadata in structured data.
- [ ] Spatie Media Library hooks for OpenGraph image extraction.
- [ ] **WebP Conversion:** Implement automatic WebP image conversion and compression utilities for uploaded media."""

new_p6 = """## Phase 6: Image SEO & Media 🖼️
**Goal:** Optimize images for discoverability and accessibility.
- [ ] **Image Alt Text Audit:** Identify missing alt text and provide quick-add links.
- [ ] **Clean Filename Enforcer:** Auto-rename uploads to lowercase, hyphen-separated filenames.
- [ ] **Image Schema:** Automatically include image dimensions and metadata in structured data.
- [ ] Spatie Media Library hooks for OpenGraph image extraction."""
content = content.replace(old_p6, new_p6)

# Revert Phase 7
old_p7 = """## Phase 7: Redirects, 404s & Technical SEO Audit 🔍
**Goal:** Server-level tools, internal crawling, and site architecture health.
- [ ] **Redirect Manager:** 301, 302, and 307 redirects with full **Regex** support.
- [ ] **404 Error Monitor:** UPSERT logging, referrer tracking, and 1-click redirects.
- [ ] **Internal Link Index:** Map all internal links into an organized architecture table.
- [ ] **Orphaned Content Finder:** Detect published posts with zero incoming links.
- [ ] **Broken Link Checker:** Chunked queuing to scan for broken internal/external links.
- [ ] **Favicon & Icon Checker:** Verify browser tabs, mobile bookmarks, and PWA icons.
- [ ] **Technical Analysis Checkers:** Detect Semantic HTML, Security (HTTPS/Headers), and i18n/hreflang issues.
- [ ] **Crawler Health Checks:** Scan for missing/multiple H1s, rogue `noindex` tags on production, and blocked Googlebot rules in `robots.txt`.
- [ ] **Redirect Chain & Loop Killer:** Detect and alert on endless redirect loops or long redirect chains."""

new_p7 = """## Phase 7: Redirects, 404s & Technical SEO Audit 🔍
**Goal:** Server-level tools, internal crawling, and site architecture health.
- [ ] **Redirect Manager:** 301, 302, and 307 redirects with full **Regex** support.
- [ ] **404 Error Monitor:** UPSERT logging, referrer tracking, and 1-click redirects.
- [ ] **Internal Link Index:** Map all internal links into an organized architecture table.
- [ ] **Orphaned Content Finder:** Detect published posts with zero incoming links.
- [ ] **Broken Link Checker:** Chunked queuing to scan for broken internal/external links.
- [ ] **Favicon & Icon Checker:** Verify browser tabs, mobile bookmarks, and PWA icons.
- [ ] **Technical Analysis Checkers:** Detect Semantic HTML, Security (HTTPS/Headers), and i18n/hreflang issues."""
content = content.replace(old_p7, new_p7)

# Revert Phase 10
old_p10 = """## Phase 10: Google Search Console API 📈
**Goal:** Deep integration with Google Search Console for advanced index tracking.
- [ ] **GSC URL Submission:** Integrate the Google Indexing API.
- [ ] **GSC Sitemap Sync & Delete:** Automatically submit `sitemap.xml`, and add capability to **delete** submitted sitemaps via API.
- [ ] **Sitemap Drift Tracker:** Unified dashboard comparing what Holvek declared vs what Google actually discovered to identify indexing drift.
- [ ] **Daily URL Monitoring:** Scheduled cron to track daily indexing and HTTP status changes for critical URLs.
- [ ] **GSC Analytics:** Fetch GSC metrics (Clicks, Impressions, CTR) natively.
  - [ ] **Google Algorithm Update Overlays:** *Note to prevent execution drift:* Overlay known Google Core Algorithm update dates as vertical markers directly on the GSC traffic charts. This allows users to instantly visually correlate traffic drops with search engine algorithm shifts.
- [ ] **GSC Index Status:** View exact index status using the URL Inspection API."""

new_p10 = """## Phase 10: Google Search Console API 📈
**Goal:** Deep integration with Google Search Console.
- [ ] **GSC URL Submission:** Integrate the Google Indexing API.
- [ ] **GSC Sitemap Submission:** Automatically submit `sitemap.xml`.
- [ ] **GSC Analytics:** Fetch GSC metrics (Clicks, Impressions, CTR) natively.
  - [ ] **Google Algorithm Update Overlays:** *Note to prevent execution drift:* Overlay known Google Core Algorithm update dates as vertical markers directly on the GSC traffic charts. This allows users to instantly visually correlate traffic drops with search engine algorithm shifts.
- [ ] **GSC Index Status:** View exact index status using the URL Inspection API."""
content = content.replace(old_p10, new_p10)

# Revert Phase 11
old_p11 = """## Phase 11: Bing, IndexNow & LLM Indexing APIs 🚀
**Goal:** Instant search engine indexing submission and cross-engine synchronization.
- [ ] **IndexNow Integration:** Batch notifications for Bing, Yandex, and Seznam.
- [ ] **Bing Indexing Status:** Check exact URL indexing status via the Bing Webmaster API.
- [ ] **Sync & Delete Sitemaps:** Synchronize sitemaps across Bing/Yandex, with the ability to delete them remotely via Webmaster APIs.
- [ ] **LLM Indexing:** Ping ChatGPT, Claude, Gemini, and Perplexity on updates.
- [ ] **Indexing Dashboard:** View submission logs, daily quota usage, and response statuses."""

new_p11 = """## Phase 11: Bing, IndexNow & LLM Indexing APIs 🚀
**Goal:** Instant search engine indexing submission.
- [ ] **IndexNow Integration:** Batch notifications for Bing, Yandex, and Seznam.
- [ ] **LLM Indexing:** Ping ChatGPT, Claude, Gemini, and Perplexity on updates.
- [ ] **Indexing Dashboard:** View submission logs, daily quota usage, and response statuses."""
content = content.replace(old_p11, new_p11)

with open(filepath, 'w') as f:
    f.write(content)
print("Tasks fixed.")
