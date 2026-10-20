# Quinrel Laravel - v1.1.0 Engineering Backlog

## Overview
This backlog outlines the advanced, enterprise-grade technical SEO capabilities targeted for the `v1.1.0` release of `quinrel-laravel`. The focus is on moving the package from standard technical hygiene (v1.0.0) to an agency/enterprise-level SEO tool.

---

## Epic 1: Advanced Redirect Management (High Priority)
**Objective:** Protect link equity and prevent crawl budget waste caused by poor redirection practices.

### Tasks
- [x] **Feature: Redirect Chain Detection**
  - Implement a background job (`DetectRedirectChains`) that periodically traverses the `quinrel_redirects` table.
  - Traverse destinations to ensure A -> B doesn't secretly go B -> C -> D.
  - Flag redirects with a `is_chain` boolean or similar state.
- [x] **Feature: Infinite Loop Prevention**
  - Add validation on redirect creation/update to prevent A -> B -> A loops.
  - Add runtime detection during the redirect resolution middleware to break and log loops.
- [x] **UI Integration**
  - Add a "Warnings" or "Health" column in the Filament/Headless Redirect Resource highlighting chains/loops.
  - Provide a 1-click "Flatten Chain" action (updating A -> B -> C to A -> C).

---

## Epic 2: IndexNow API Integration (High Priority)
**Objective:** Enable instant crawling and indexing on Bing, Yandex, and other participating search engines.

### Tasks
- [x] **Feature: IndexNow Key Generation & Hosting**
  - Generate a unique 32-character IndexNow key on package installation.
  - Automatically host the key text file at the web root (e.g., `/{key}.txt`) via a dedicated route, as required by the protocol.
- [x] **Feature: Ping Submission Logic**
  - Create an `IndexNowService` that sends HTTP POST requests to the IndexNow API endpoints.
  - Support batch URL submissions.
- [x] **Feature: Eloquent Model Observers**
  - Hook into model `created`, `updated`, and `deleted` events for models using the `HasQuinrelMeta` trait.
  - Dispatch a queued job to ping IndexNow when an indexable model changes.
- [x] **Configuration**
  - Add config flags to enable/disable IndexNow and select target search engines.

---

## Epic 3: Internationalization (i18n) & Hreflang (Medium Priority)
**Objective:** Support global applications with automated Hreflang tag generation and validation.

### Tasks
- [x] **Feature: Hreflang Meta Tag Generation**
  - Extend the `Quinrel` SEO service to output `<link rel="alternate" hreflang="x" href="y" />` tags.
  - Define an interface or configuration convention for host applications to define their locale-to-URL mappings.
- [x] **Feature: Hreflang Validator (Console/Job)**
  - Validate common Hreflang errors: Missing return tags (if A links to B, B must link to A), invalid ISO language/region codes, missing `x-default`.
- [x] **Feature: Multi-locale XML Sitemaps**
  - Update the XML Sitemap generator to use the `<xhtml:link>` schema for localized URLs.

---

## Epic 4: Google Search Console (GSC) Integration (Medium Priority)
**Objective:** Bring actual organic performance data (Clicks, Impressions, CTR, Position) directly into the Laravel dashboard.

### Tasks
- [x] **Feature: OAuth / Service Account Setup**
  - Provide configuration structure for Google API credentials (Service Account JSON).
- [x] **Feature: Data Aggregation Command**
  - Create an Artisan command (`quinrel:fetch-gsc-data`) to run daily via scheduler.
  - Query the GSC API for site-wide and URL-specific metrics.
- [x] **Feature: Data Storage & Pruning**
  - Create a lightweight time-series table (or cache strategy) to store daily GSC metrics without bloating the database.
- [x] **UI Integration**
  - Build a Filament widget for the dashboard showing the 30-day organic performance trend.
  - Show URL-specific performance data inside the `QuinrelMetaResource` edit page.

---

## Epic 5: Real-User Monitoring (RUM) for Core Web Vitals (Low/Medium Priority)
**Objective:** Measure actual browser performance (LCP, INP, CLS) from real visitors rather than relying solely on lab data.

### Tasks
- [x] **Feature: Telemetry Script Injection**
  - Provide a Blade directive (e.g., `@quinrelRum`) that safely injects the standard `web-vitals` JS library.
- [x] **Feature: Ingestion Endpoint**
  - Create a high-throughput, unauthenticated API endpoint (with rate limiting) to receive telemetry beacons from browsers.
- [x] **Feature: Aggregation & Storage**
  - Use Redis or batch database inserts to handle high-volume telemetry data.
  - Aggregate scores at the 75th percentile (Google's standard metric).
- [x] **UI Integration**
  - Display real-time RUM data vs. Lab data in the SEO dashboard.

---

## Next Steps for Execution
1. Pick an Epic to begin implementation.
2. Review architecture for the selected Epic to ensure it aligns with `AGENTS.md` (no unnecessary dependencies, lean implementation).
3. Execute TDD (Test-Driven Development) for the new features.
