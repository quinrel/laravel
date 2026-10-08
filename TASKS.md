# Implementation Plan (TASKS.md)

This document outlines the phased execution plan for the `holvek-laravel` package. Each phase is designed to be independently verifiable, incorporating all features outlined in `features.md`, and adheres to the architectural guidelines in `AGENTS.md`.

## Phase 1: Foundation, Tooling & Setup Wizard 🏗️
**Goal:** Establish the repository structure, code quality tools, and Laravel package scaffolding.
- [ ] **Local-First Architecture:** Ensure core features function 100% offline without requiring a Holvek Cloud API key.
- [ ] Initialize `composer.json` with correct Laravel 11/12/13 constraints and code tooling (PHPUnit, Larastan, Pint).
- [ ] Create the core `HolvekServiceProvider` (config, migrations, routes).
- [ ] Scaffold the React SPA dashboard and **Filament / TALL Stack** Resource pages.
- [ ] **Setup Wizard:** Implement a 3-step setup process for site type, social links, and defaults.
- [ ] **Competitor Migration:** Implement 1-click importers for `artesaos/seotools`, `ralphjsmit/laravel-seo`, and `spatie/laravel-seo`.
- [ ] **User Roles & Team Access:** Add granular SEO permissions (Admin, Editor, Author).
- [ ] **Audit History Log:** Record every change made to SEO settings, showing the user and timestamp.

## Phase 2: Core Metadata, Social & Head Component 📝
**Goal:** Define and render SEO metadata, canonicals, and environment profiles.
- [ ] Create the `holvek_meta` database migration and `HasHolvekMeta` trait.
- [ ] **Route Macros:** Implement `Route::macro('seo', ...)` with **Wildcard matching** support.
- [ ] Develop the `@holvekHead` Blade directive for titles, descriptions, and custom canonicals.
- [ ] Implement **Social Media Previews** (Facebook, X, LinkedIn, Instagram, TikTok) and OpenGraph/Twitter Cards.
- [ ] **Environment-Aware Profiles:** Automatically inject `noindex, nofollow` on non-production environments.
- [ ] **Robots Meta Controls:** UI to set `noindex`, `nofollow`, `noarchive`, `nosnippet`, `noimageindex` per page.
- [ ] **Aggressive Caching Layer:** Cache rendered HTML strings and invalidate via Observers.
- [ ] **SPA Native Adapters (Inertia.js & Livewire):** 
  - *Note to prevent execution drift:* Standard Blade rendering fails on client-side SPA navigation. Build `Holvek::inertia()` and `Holvek::livewire()` middleware to automatically intercept and share `@holvekHead` state with the client-side router, preserving SEO metadata during AJAX navigations.

## Phase 3: Content Editor & UI Tools ✍️
**Goal:** Provide real-time feedback and assistance during content creation.
- [x] **On-Page SEO Checklist:** Real-time analysis for title length, meta description length, keyword usage, headings, and alt text.
- [x] **Readability Checker:** Flesch-Kincaid scale analysis highlighting passive voice and long sentences.
- [x] **Search Result Preview:** Accurate live preview for Google/Bing on desktop and mobile.
- [x] **Internal Link Suggestions:** Recommend relevant internal articles while writing.
- [x] **Cornerstone Content:** Flag important posts and monitor for 180-day staleness.

## Phase 4: Sitemaps, Crawl Control & AI Guides 🗺️
**Goal:** Comprehensive crawling, sitemaps, and LLM text configurations.
- [ ] Build the core sitemap generator with automatic monthly chunking (`sitemap.xml`).
  - [ ] **Memory-Safe Sitemap Streaming:** *Note to prevent execution drift:* Do not load models into RAM. Use PHP Generators (`yield`) and unbuffered Eloquent queries (`lazy()`) to stream XML directly to disk/S3. This prevents fatal memory exhaustion on datasets exceeding 100,000+ URLs.
- [ ] **News Sitemap:** Generator for `sitemap-news.xml` (last 48 hours).
- [ ] **Video Sitemap:** Generator for `video-sitemap.xml` (extracting embedded videos).
- [ ] **HTML Sitemap:** Create `<x-holvek-sitemap />` Blade component for user-facing navigation.
- [ ] **Robots.txt Editor:** Allow safe custom document replacement.
- [ ] **.htaccess Editor:** Optional tool for Apache users to manage redirects/rules.
- [ ] **AI Crawler Guide:** Generate dynamic `llms.txt` and `llms-full.txt` with customizable summaries.
- [ ] **AEO Markdown Endpoints (`.md` / `.llm`):** 
  - *Note to prevent execution drift:* AI scrapers (ChatGPT, Perplexity) hallucinate when parsing heavy HTML layouts. Implement a route suffix (e.g., `/{slug}.md`) or Content-Negotiation layer that bypasses the Blade layout and returns pure, clean Markdown text to ensure accurate AI ingestion.

## Phase 5: Structured Data (Schema.org) 🧩
**Goal:** Provide a fluent API for generating JSON-LD structured data.
- [ ] Implement the Schema Generator service.
- [ ] Support core schemas: **Article, NewsArticle, WebPage, WebSite, Breadcrumb, FAQPage, How-To, LocalBusiness, Recipe, Event, Product**.
- [ ] **E-E-A-T Schema Automation (Experience, Expertise, Authoritativeness, Trust):** 
  - *Note to prevent execution drift:* Standard Article schema is insufficient against AI-content ranking penalties. Automate deeply nested `Person` (Author) schema linking to `Organization`, injecting `sameAs` social links and bio credentials directly into the JSON-LD payload to strongly signal trust to algorithms.
- [ ] **Custom Schema:** Allow pasting custom JSON-LD directly into the UI.

## Phase 6: Image SEO & Media 🖼️
**Goal:** Optimize images for discoverability and accessibility.
- [ ] **Image Alt Text Audit:** Identify missing alt text and provide quick-add links.
- [ ] **Clean Filename Enforcer:** Auto-rename uploads to lowercase, hyphen-separated filenames.
- [ ] **Image Schema:** Automatically include image dimensions and metadata in structured data.
- [ ] Spatie Media Library hooks for OpenGraph image extraction.

## Phase 7: Redirects, 404s & Technical SEO Audit 🔍
**Goal:** Server-level tools, internal crawling, and site architecture health.
- [ ] **Redirect Manager:** 301, 302, and 307 redirects with full **Regex** support.
- [ ] **404 Error Monitor:** UPSERT logging, referrer tracking, and 1-click redirects.
- [ ] **Internal Link Index:** Map all internal links into an organized architecture table.
- [ ] **Orphaned Content Finder:** Detect published posts with zero incoming links.
- [ ] **Broken Link Checker:** Chunked queuing to scan for broken internal/external links.
- [ ] **Favicon & Icon Checker:** Verify browser tabs, mobile bookmarks, and PWA icons.
- [ ] **Technical Analysis Checkers:** Detect Semantic HTML, Security (HTTPS/Headers), and i18n/hreflang issues.

## Phase 8: Analytics, Performance & PageSpeed ⚡
**Goal:** Monitor Core Web Vitals, GA4, and overall page performance.
- [ ] **Google Analytics 4 (GA4):** Integrate reports (session trends, top landing pages) directly into the SPA.
- [ ] **PageSpeed Insights:** Fetch mobile/desktop Core Web Vitals (LCP, INP, CLS) cached for 7 days.
- [ ] **Keyword Cannibalization Alert:** Detect multiple internal pages competing for the exact same query.
- [ ] **Client Reporting:** Generate branded, white-label reports (JSON, CSV, HTML formats).

## Phase 9: Keyword Rank Tracker 📊
**Goal:** Track and log keyword rankings.
- [ ] Integrate **SerpApi** to track target keywords over time.
- [ ] Build 7-day, 30-day, and 90-day history charts natively in the dashboard.

## Phase 10: Google Search Console API 📈
**Goal:** Deep integration with Google Search Console.
- [ ] **GSC URL Submission:** Integrate the Google Indexing API.
- [ ] **GSC Sitemap Submission:** Automatically submit `sitemap.xml`.
- [ ] **GSC Analytics:** Fetch GSC metrics (Clicks, Impressions, CTR) natively.
  - [ ] **Google Algorithm Update Overlays:** *Note to prevent execution drift:* Overlay known Google Core Algorithm update dates as vertical markers directly on the GSC traffic charts. This allows users to instantly visually correlate traffic drops with search engine algorithm shifts.
- [ ] **GSC Index Status:** View exact index status using the URL Inspection API.

## Phase 11: Bing, IndexNow & LLM Indexing APIs 🚀
**Goal:** Instant search engine indexing submission.
- [ ] **IndexNow Integration:** Batch notifications for Bing, Yandex, and Seznam.
- [ ] **LLM Indexing:** Ping ChatGPT, Claude, Gemini, and Perplexity on updates.
- [ ] **Indexing Dashboard:** View submission logs, daily quota usage, and response statuses.

## Phase 12: Holvek Cloud, Webhooks & REST API ☁️
**Goal:** Cloud offloading, discovery scans, and headless support.
- [ ] **Headless REST API:** Build `/holvek/v1/seo/{post_id}` and `/global` endpoints for Next.js/Nuxt.
- [ ] **Discovery Check (`/scan`):** Free snapshot (technical score, competitor comparison, AI mention check).
- [ ] Cloud API Integration for heavy auditing and Webhook HMAC Security.

## Phase 13: AI Generative APIs & Privacy Compliance 🤖
**Goal:** AI assistance, legal compliance, and final polish.
- [ ] **AI Writing Assistant:** Suggest titles/meta using OpenAI, Anthropic, or Ollama (default).
- [ ] **Cookie Notice:** First-party notice recording visitor choices.
- [ ] **AI Content Disclosure:** UI to add notices when content is AI-assisted.
- [ ] **Multi-Tenant Data Security:** Verify all queries dynamically resolve tenant connections.
- [ ] Final security audit, N+1 query optimization, and tag `v1.0.0` release.
