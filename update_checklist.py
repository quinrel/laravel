import os

filepath = '/Applications/XAMPP/xamppfiles/htdocs/holvek-laravel/TASKS.md'
with open(filepath, 'r') as f:
    content = f.read()

# WebP Conversion in Phase 6
old_p6 = """## Phase 6: Image SEO & Media 🖼️
**Goal:** Optimize images for discoverability and accessibility.
- [ ] **Image Alt Text Audit:** Identify missing alt text and provide quick-add links.
- [ ] **Clean Filename Enforcer:** Auto-rename uploads to lowercase, hyphen-separated filenames.
- [ ] **Image Schema:** Automatically include image dimensions and metadata in structured data.
- [ ] Spatie Media Library hooks for OpenGraph image extraction."""

new_p6 = """## Phase 6: Image SEO & Media 🖼️
**Goal:** Optimize images for discoverability and accessibility.
- [ ] **Image Alt Text Audit:** Identify missing alt text and provide quick-add links.
- [ ] **Clean Filename Enforcer:** Auto-rename uploads to lowercase, hyphen-separated filenames.
- [ ] **Image Schema:** Automatically include image dimensions and metadata in structured data.
- [ ] Spatie Media Library hooks for OpenGraph image extraction.
- [ ] **WebP Conversion:** Implement automatic WebP image conversion and compression utilities for uploaded media."""

content = content.replace(old_p6, new_p6)

# Redirect chains, multiple H1s, rogue noindex, unblock googlebot in Phase 7
old_p7 = """## Phase 7: Redirects, 404s & Technical SEO Audit 🔍
**Goal:** Server-level tools, internal crawling, and site architecture health.
- [ ] **Redirect Manager:** 301, 302, and 307 redirects with full **Regex** support.
- [ ] **404 Error Monitor:** UPSERT logging, referrer tracking, and 1-click redirects.
- [ ] **Internal Link Index:** Map all internal links into an organized architecture table.
- [ ] **Orphaned Content Finder:** Detect published posts with zero incoming links.
- [ ] **Broken Link Checker:** Chunked queuing to scan for broken internal/external links.
- [ ] **Favicon & Icon Checker:** Verify browser tabs, mobile bookmarks, and PWA icons.
- [ ] **Technical Analysis Checkers:** Detect Semantic HTML, Security (HTTPS/Headers), and i18n/hreflang issues."""

new_p7 = """## Phase 7: Redirects, 404s & Technical SEO Audit 🔍
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

content = content.replace(old_p7, new_p7)

with open(filepath, 'w') as f:
    f.write(content)
print("Tasks Updated Successfully")
