# Changelog

All notable changes to `quinrel/laravel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-10-24

### Added
- **Google Index Status Diagnostics & Action Plans:**
  - `GscInspectionNormalizer` and immutable `GscInspectionDiagnosis` DTO normalizing Google Search Console URL Inspection API payloads (`verdict`, `coverageState`, `indexingState`, `robotsTxtState`, canonical mismatch detection, `crawledAs`, `lastCrawlTime`).
  - `GscActionPlanGenerator` translating coverage failure states (`Disallowed by robots.txt`, `Duplicate, Google chose different canonical than user`, `Crawled - currently not indexed`, `Soft 404`) into prioritized developer action plans with exact `robots.txt` blocking rule detection via `RobotsTxtManager::findBlockingRule()`.
  - 1-click **Adopt Google Canonical** action (`GoogleIndexingService::adoptGoogleCanonical()`) and automatic `PingGoogleIndexingJob` + `PingIndexNowJob` dispatch when an excluded `QuinrelMeta` record is resolved.
  - `GscIndexStatusWidget` and interactive **Index Status & Remediation** tab in `QuinrelMetaResource`.
- **SEO Crawl Orchestration & Automated Remediation:**
  - `SiteCrawlOrchestrator` and `quinrel:crawl` Artisan command for streaming concurrent `Http::pool()` site crawls with `UrlValidator` SSRF protection and `LazyCollection` memory efficiency.
  - `SeoCrawlActionPlanService` categorizing crawl findings into prioritized `P0`–`P3` remediation tickets and calculating a 0–100 health score.
  - `AutoRedirectCreator` for Levenshtein/similarity-based 404-to-redirect matching, optional auto-flattening of redirect chains (`quinrel.redirects.auto_flatten_chains`), and `quinrel:autofix-meta` batch AI metadata generation command.
  - Filament `CrawlDashboard` page and standalone React `DiagnosticsPanel`.
- **Lighthouse & PageSpeed Insights Diagnostics & Action Plans:**
  - Expanded `PageSpeedInsights` and immutable `LighthouseAuditResult` DTO ingesting `performance`, `seo`, and `best-practices` categories alongside detailed opportunities (`render-blocking-resources`, `largest-contentful-paint-element`, `layout-shift-elements`, `unminified-css`, `unminified-javascript`, `uses-optimized-images`, `modern-image-formats`, `long-tasks`).
  - `PageSpeedActionPlanService` translating Lighthouse audits into Laravel/Blade/Vite remediation steps.
  - `@quinrelPreload` Blade directive, `<x-quinrel::image>` (`<x-quinrel-image>`) component, and `HeadRenderer::optimizeContentImages()` helper.
  - Filament `PageSpeedOpportunitiesWidget`.
- **Accessibility (a11y) Static Audit Engine & Remediation:**
  - Native DOM-based `AccessibilityAuditor` checking WCAG 2.1/2.2 AA rules (document language, image alt text & placeholder filenames, form input labeling, heading hierarchy, interactive element accessible names, duplicate IDs).
  - `AccessibilityActionPlanService` generating line-numbered remediation tickets with drop-in HTML fixes.
  - `AiAltTextGenerator`, `quinrel:autofix-alt-text`, and `quinrel:audit-a11y` Artisan commands, plus Filament `AccessibilityScoreWidget` and **Generate Alt Text with AI** action.
- **Unified Audit Suite, CI/CD Gates & Executive Reporting:**
  - `UnifiedAuditCoordinator` and `quinrel:audit-all` command uniting all 4 diagnostic pillars in a single pass.
  - `quinrel:ci-check` CI/CD pipeline quality gate exiting with code `1` when critical issues or composite score breach configured thresholds.
  - Upgraded `ClientReportGenerator` and `quinrel::reports.executive` template for HTML, PDF, and CSV executive reports.
- **Standalone GEO (Generative Engine Optimization) Toolkit:**
  - RFC 9309 AI bot auditing (`isBotAllowed()`, `auditAiBots()`) and idempotent `# BEGIN Quinrel GEO` policy management (`applyGeoPolicy()`) in `RobotsTxtManager`, distinguishing AI Search/Citation bots (`OAI-SearchBot`, `ChatGPT-User`, `PerplexityBot`, `Claude-SearchBot`, `Claude-User`, `Applebot`) from AI Training scrapers (`GPTBot`, `ClaudeBot`, `Google-Extended`, `CCBot`, `Bytespider`).
  - Registered `quinrel.aeo` middleware alias (`Quinrel::aeo()`) for `AeoContentNegotiationMiddleware` with `Vary: Accept` header support and automatic `<link rel="alternate" type="text/markdown" href="...?format=md">` advertisement in `HeadRenderer`.
  - Aligned `config('quinrel.llms_txt.description')` in `LlmsTxtController` and added Markdown content negotiation documentation to `/llms-full.txt`.
  - Added local GEO citability scoring (`analyzeGeo()`, direct answer capsule detection, structured list/table checks, and quantitative factual density analysis) to `SeoChecklist` and `ContentAnalyzer`, plus `TechnicalSeoAuditor::auditGeoReadiness()`.

### Removed
- Consolidated redundant incremental schema migrations into the base `quinrel_meta` and `quinrel_redirects` migrations and removed unused legacy stub classes (`IndexNowClient`, `LlmIndexingPinger`, `SitemapIndexGenerator`, `FaviconChecker`, `HtaccessManager`).

## [1.1.0] - 2026-10-09

### Added
- **Redirect Management:** Infinite loop detection and redirect chain flattening capabilities in middleware and the Filament dashboard.
- **IndexNow API:** Automated ping logic to IndexNow endpoints (Bing, Yandex) upon SEO model creation or update, configurable via `quinrel.indexnow`.
- **Internationalization (i18n):** Automated `hreflang` meta tag injection and support for `<xhtml:link>` elements in XML Sitemaps.
- **Google Search Console (GSC):** Dashboard integration displaying 30-day organic performance (clicks, impressions, position, CTR) site-wide and per-URL via a new artisan command (`quinrel:fetch-gsc-data`).
- **Core Web Vitals (RUM):** Real-User Monitoring ingestion via the `@quinrelRum` Blade directive and a high-throughput endpoint to measure LCP, INP, TTFB, FCP, and CLS directly from site visitors.

### Fixed
- Static analysis strict typing compliance across all new models and API services.

## [1.0.0] - Initial Release
- Basic SEO, Sitemap generation, and Head metadata management.
