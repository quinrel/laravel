# Quinrel Laravel — v1.2.0 Engineering Backlog: Diagnostics, Autofix & Action Plans

## Overview
This backlog defines the engineering roadmap for **Quinrel Laravel v1.2.0**. Building upon the foundation of v1.0.0 (core metadata, sitemaps, head rendering) and v1.1.0 (redirect management, IndexNow, GSC metrics, RUM telemetry), this release transforms Quinrel from a passive monitoring/metadata tool into an **active diagnostic, autofixing, and remediation engine** across four core pillars:
1. **Google Index Status Diagnostics & Action Plans**
2. **SEO Crawl Orchestration & Automated Remediation**
3. **Lighthouse / PageSpeed Insights Opportunities & Performance Action Plans**
4. **Accessibility (a11y) Static Audit Engine & Remediations**

---

## Prioritization & Execution Sequence

```text
┌────────────────────────────────────────────────────────────────────────┐
│ Phase 1: High Priority (P0) — Immediate SEO Impact                     │
│   • Epic 1: Google Index Status Diagnostics & Action Plans             │
│   • Epic 2: Full SEO Crawl Orchestration & Automated Remediation       │
├────────────────────────────────────────────────────────────────────────┤
│ Phase 2: Medium Priority (P1) — Quality & Performance Expansion        │
│   • Epic 3: Lighthouse / PageSpeed Insights Opportunities & CWV Plans  │
│   • Epic 4: Accessibility (a11y) Audit Engine & AI Alt-Text Autofix    │
├────────────────────────────────────────────────────────────────────────┤
│ Phase 3: Operational (P2) — CI/CD & Unified Reporting                  │
│   • Epic 5: Unified Audit Suite, CI/CD Gates & Executive Reports       │
└────────────────────────────────────────────────────────────────────────┘
```

---

## Epic 1: Google Index Status Diagnostics & Automated Action Plans (P0 — High Priority)

**Objective:** Transform raw Google Search Console URL Inspection API payloads into actionable diagnostic findings, prioritized developer action plans, and automated re-submission workflows.

### Current Gap
[`GscIndexStatus::check($url)`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Tracking/GscIndexStatus.php) and [`GscClient::inspectUrl($url)`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Tracking/GscClient.php) return the raw GSC JSON response payload without interpretation. Developers are left to decode Google's internal terminology (`coverageState`, `indexingState`, `robotsTxtState`, `userCanonical`, `googleCanonical`).

### Tasks

- [x] **Task 1.1: GSC URL Inspection Payload Normalizer & DTO**
  * **Location:** `src/Services/Tracking/GscInspectionNormalizer.php`, `src/Data/GscInspectionDiagnosis.php`
  * **Description:** Parse Google Search Console's `inspectionResult.indexStatusResult` into a strongly-typed, immutable DTO:
    * `verdict` (`PASS`, `NEUTRAL`, `FAIL`)
    * `coverageState` (e.g., `Submitted and indexed`, `Crawled - currently not indexed`, `Disallowed by robots.txt`, `Duplicate without user-selected canonical`, `Soft 404`, `Page with redirect`)
    * `indexingState` (`INDEXING_ALLOWED`, `BLOCKED_BY_META_TAG`, `BLOCKED_BY_HTTP_HEADER`)
    * `robotsTxtState` (`ALLOWED`, `DISALLOWED`)
    * `userCanonical` vs `googleCanonical` mismatch detection
    * `crawledAs` (`MOBILE`, `DESKTOP`)
    * `lastCrawlTime` (`CarbonInterface|null`)
  * **Verification:** Unit tests with real Google API fixtures covering all standard coverage states.

- [x] **Task 1.2: Rule-Based Google Indexing Action Plan Generator**
  * **Location:** `src/Services/Tracking/GscActionPlanGenerator.php`
  * **Description:** Create an expert rules engine that translates diagnosed failure states into concrete remediation plans:
    * *Blocked by robots.txt:* Identify the conflicting `Disallow` rule in `public/robots.txt` via [`RobotsTxtManager`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/RobotsTxtManager.php) and provide exact directive fix.
    * *Canonical Mismatch:* Detect when Google picked a different canonical than the user; recommend standardizing internal links or updating `quinrel_meta.canonical_url`.
    * *Crawled - Currently Not Indexed:* Generate content improvement plan (evaluate thin content, recommend internal inbound links via [`InternalLinkSuggester`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Analysis/InternalLinkSuggester.php), and check search intent).
    * *Soft 404 / 404:* Recommend establishing a redirect via [`QuinrelRedirect`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Models/QuinrelRedirect.php) or returning true HTTP 404/410.
  * **Verification:** Feature tests asserting expected action items and priority rankings for each coverage failure state.

- [x] **Task 1.3: 1-Click Autofix & Resubmission Actions**
  * **Location:** `src/Filament/Resources/QuinrelMetaResource.php`, `src/Services/Indexing/GoogleIndexingService.php`
  * **Description:**
    * Provide a 1-click **"Adopt Google Canonical"** action when Google has overridden the user's declared canonical, updating `quinrel_meta.canonical_url` to match `googleCanonical`.
    * Add automated queue hook: when an indexing-excluded model or meta record is resolved, automatically dispatch [`PingGoogleIndexingJob`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Jobs/PingGoogleIndexingJob.php) and [`PingIndexNowJob`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Jobs/PingIndexNowJob.php).
  * **Verification:** Test asserting canonical update and job dispatching upon action execution.

- [x] **Task 1.4: Filament & Standalone UI Integration**
  * **Location:** `src/Filament/Widgets/GscIndexStatusWidget.php`, `resources/views/filament/widgets/gsc-index-status.blade.php`
  * **Description:** Add an interactive "Index Status & Remediation" tab to `QuinrelMetaResource`:
    * Visual health badge: Green (Indexed), Yellow (Warning/Canonical Mismatch), Red (Excluded/Blocked).
    * Card list displaying the generated Action Plan with severity tags (`Critical`, `Warning`, `Recommendation`).
    * "Inspect Live URL Now" button with bounded rate-limit throttling.

---

## Epic 2: Comprehensive SEO Crawl Orchestration & Automated Remediation (P0 — High Priority)

**Objective:** Combine existing isolated technical checks into an integrated site-wide crawl engine that produces categorized action plans and enables 1-click automatic remediation.

### Current Gap
Technical audit capabilities exist in isolation: [`TechnicalSeoAuditor`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Technical/TechnicalSeoAuditor.php) only checks single URLs, [`BrokenLinkChecker`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Technical/BrokenLinkChecker.php) requires manual arrays of URLs, and [`OrphanedContentFinder`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Technical/OrphanedContentFinder.php) runs in isolation. There is no automated site-level crawl coordinator or consolidated remediation report.

### Tasks

- [x] **Task 2.1: Native Site Crawl Orchestrator**
  * **Location:** `src/Services/Technical/SiteCrawlOrchestrator.php`, `src/Console/Commands/CrawlSiteCommand.php`
  * **Description:** Build a streaming crawl coordinator that discovers and audits all published application URLs:
    * Discovers seed URLs from registered route names, active `QuinrelMeta` records, and generated XML sitemaps.
    * Uses `Http::pool()` in concurrent chunks (default 10) with SSRF checks ([`UrlValidator`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Support/UrlValidator.php)).
    * Streams memory using `LazyCollection` to support large catalogs (10,000+ pages) without memory exhaustion.
    * Artisan Command: `php artisan quinrel:crawl {--limit=500} {--include-models}`.
  * **Verification:** Tests simulating internal site graph using `Http::fake()` validating chunking, memory stability, and error isolation.

- [x] **Task 2.2: SEO Crawl Action Plan Engine**
  * **Location:** `src/Services/Technical/SeoCrawlActionPlanService.php`
  * **Description:** Aggregate findings from the crawl into prioritized, categorized remediation tickets:
    * **P0 (Critical):** Internal 404/500 links, circular redirect loops, missing `<title>` tags, duplicate canonicals.
    * **P1 (High):** Redirect chains (`A → B → C`), orphaned content (pages with zero internal links), missing H1 headings.
    * **P2 (Medium):** Title/description length violations, missing `apple-touch-icon`, missing HSTS headers, low word count.
    * **P3 (Low):** Internal linking opportunities suggested by [`InternalLinkSuggester`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Analysis/InternalLinkSuggester.php).
  * **Verification:** Feature test verifying score calculation and structured JSON/array output format.

- [x] **Task 2.3: Automated Remediation (Autofix Actions)**
  * **Location:** `src/Services/Technical/Remediation/AutoRedirectCreator.php`, `src/Jobs/DetectRedirectChains.php`
  * **Description:**
    * **404 to Redirect Converter:** When internal 404s or top 404 logs are detected, find the closest matching valid URL (via Levenshtein/similar_text slug matching) and provide a 1-click "Create Suggested Redirect" action.
    * **Scheduled Chain Flattening:** Allow optional auto-flattening of all detected redirect chains via `config('quinrel.redirects.auto_flatten_chains', false)`.
    * **Batch AI Meta Generation:** Provide command `php artisan quinrel:autofix-meta {--empty-only}` using [`AiWritingAssistant`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/AI/AiWritingAssistant.php) to automatically draft missing titles and descriptions.
  * **Verification:** Test verifying redirect creation from 404s and automated chain flattening without infinite loops.

- [x] **Task 2.4: Crawl Health Dashboard in Filament & Standalone UI**
  * **Location:** `src/Filament/Pages/CrawlDashboard.php`, `resources/views/filament/pages/crawl-dashboard.blade.php`
  * **Description:** Add a dedicated "Crawl Health" page displaying:
    * Total pages crawled, overall health score (0–100%).
    * Breakdown of Critical, Warning, and Info tickets.
    * One-click "Run Crawl Now" action with background queue progress.

---

## Epic 3: Lighthouse & PageSpeed Insights Diagnostics & Action Plans (P1 — Medium Priority)

**Objective:** Expand PageSpeed Insights beyond basic scores to ingest detailed Lighthouse opportunity audits, translating them into Laravel-specific performance action plans and preventive helpers.

### Current Gap
[`PageSpeedInsights`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Analytics/PageSpeedInsights.php) only fetches `category=performance` and extracts 4 raw numbers (`lcp`, `inp`, `cls`, `score`). It discards all Lighthouse diagnostic audits (render-blocking scripts, unoptimized images, layout shift elements).

### Tasks

- [x] **Task 3.1: Expanded Lighthouse Opportunity & Diagnostics Ingestion**
  * **Location:** `src/Services/Analytics/PageSpeedInsights.php`, `src/Data/LighthouseAuditResult.php`
  * **Description:** Expand API parameters to fetch `category=performance`, `category=seo`, `category=best-practices`, and parse `lighthouseResult.audits`:
    * `render-blocking-resources` (wasted ms and list of blocking CSS/JS URLs)
    * `largest-contentful-paint-element` (DOM snippet and node identifier)
    * `layout-shift-elements` (elements contributing to CLS)
    * `unminified-css` / `unminified-javascript` (potential byte savings)
    * `uses-optimized-images` / `modern-image-formats` (uncompressed image URLs)
    * `long-tasks` (tasks exceeding 50ms impacting INP)
  * **Verification:** Unit tests asserting correct extraction of audit opportunities from Google PSI response fixtures.

- [x] **Task 3.2: Laravel-Specific Performance Action Plan Generator**
  * **Location:** `src/Services/Analytics/PageSpeedActionPlanService.php`
  * **Description:** Translate Lighthouse audit IDs into actionable, framework-specific instructions:
    * *LCP Element is an `<img>`:* Instruct developer to add `<link rel="preload" as="image" href="...">` or `fetchpriority="high"`.
    * *Render-blocking scripts:* Recommend adding `defer` or migrating scripts to Vite module bundling.
    * *Layout Shift (CLS):* Flag images or containers lacking explicit `width`, `height`, or CSS `aspect-ratio`.
    * *Main-Thread Blocking (INP):* Suggest deferring non-critical scripts (Google Tag Manager, analytics) after interaction or using `requestIdleCallback`.
  * **Verification:** Test verifying mapping of audit opportunities to tailored Laravel recommendations.

- [x] **Task 3.3: Automated Performance Helpers & Directives**
  * **Location:** `src/Services/HeadRenderer.php`, `src/View/Components/Image.php`
  * **Description:**
    * Provide a `@quinrelPreload($url, $as)` Blade directive to streamline preloading critical LCP assets.
    * Add automated HTML post-processing helper for model content: ensure all `<img>` tags inside post bodies have `loading="lazy"` and `decoding="async"` automatically attached unless marked as hero.
  * **Verification:** Blade rendering tests asserting correct markup attributes and preload header injection.

- [x] **Task 3.4: Enhanced Filament Performance Widget**
  * **Location:** `src/Filament/Widgets/PageSpeedOpportunitiesWidget.php`
  * **Description:** Add a Filament widget listing top 5 performance opportunities with estimated time savings (e.g., *"Save 420ms by removing render-blocking CSS"*).

---

## Epic 4: Accessibility (a11y) Static Audit Engine & Remediation (P1 — Medium Priority)

**Objective:** Introduce a native, rule-based static accessibility audit engine to evaluate core WCAG 2.1/2.2 AA requirements on rendered templates, coupled with AI-assisted alt text generation.

### Current Gap
The package only contains [`ImageAltTextAuditor`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Media/ImageAltTextAuditor.php), a regex check for empty alt tags. There is no WCAG checking, no automated fixes, and no accessibility action planning.

### Tasks

- [x] **Task 4.1: Static HTML/Blade Accessibility Auditor**
  * **Location:** `src/Services/Accessibility/AccessibilityAuditor.php`
  * **Description:** Build an efficient DOM-based static auditor checking essential WCAG 2.1/2.2 AA rules on rendered HTML:
    * **Image Alt Text:** Missing `alt`, empty `alt` on non-decorative images, or placeholder filenames (e.g., `alt="image.png"`, `alt="DSC_0001"`).
    * **Form Labeling:** `<input>`, `<select>`, `<textarea>` lacking an associated `<label for="...">`, `aria-label`, or `aria-labelledby`.
    * **Heading Hierarchy:** Missing `<h1>`, multiple `<h1>` elements, or skipped heading levels (e.g. `<h1>` directly to `<h3>`).
    * **Document Language:** Missing or invalid `<html lang="...">` attribute.
    * **Accessible Names for Interactive Elements:** `<button>` and `<a>` elements containing only SVG icons with no accessible text or `aria-label`.
    * **Duplicate Element IDs:** Identify duplicate HTML `id` attributes that break screen reader navigation.
  * **Verification:** Unit tests asserting detection across diverse positive and negative HTML snippets.

- [x] **Task 4.2: AI-Powered Alt-Text Autofix Engine**
  * **Location:** `src/Services/Media/AiAltTextGenerator.php`, `src/Console/Commands/GenerateImageAltTextCommand.php`
  * **Description:**
    * Use [`AiWritingAssistant`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/AI/AiWritingAssistant.php) to analyze surrounding content, image filename, and article context to generate descriptive, accessible alt text.
    * Add a 1-click action in Filament: **"Generate Alt Text with AI"** on any media or image record lacking an alt attribute.
    * Command: `php artisan quinrel:autofix-alt-text {--model=Post}`.
  * **Verification:** Feature tests mocking AI provider and verifying updated database/meta attributes.

- [x] **Task 4.3: Accessibility Action Plan Generator**
  * **Location:** `src/Services/Accessibility/AccessibilityActionPlanService.php`
  * **Description:** Group accessibility violations by severity (**Critical / Serious / Moderate**) and generate clear remediation tickets with exact code snippets:
    * E.g., showing exact line number, failing HTML snippet, and recommended drop-in replacement (such as adding `aria-label="Close modal"` to an icon-only button).
  * **Verification:** Test asserting ticket creation, severity ranking, and code replacement suggestions.

- [x] **Task 4.4: Accessibility CLI & Filament Integration**
  * **Location:** `src/Console/Commands/AuditAccessibilityCommand.php`, `src/Filament/Widgets/AccessibilityScoreWidget.php`
  * **Description:**
    * Artisan Command: `php artisan quinrel:audit-a11y {url?}`.
    * Filament widget showing compliance percentage, violation counts, and 1-click remediation triggers.

---

## Epic 5: Unified Audit Reporting, CLI & CI/CD Verification (P2 — Operational)

**Objective:** Unite all 4 diagnostic pillars into a cohesive command-line suite and client-ready reporting system suitable for continuous deployment pipelines.

### Tasks

- [x] **Task 5.1: Unified Audit Suite Coordinator**
  * **Location:** `src/Services/Reporting/UnifiedAuditCoordinator.php`, `src/Console/Commands/AuditAllCommand.php`
  * **Description:** Coordinate execution of Technical SEO, Google Indexing, PageSpeed, and Accessibility audits in a single pass.
  * Command: `php artisan quinrel:audit-all {url?}` with rich CLI table output.

- [x] **Task 5.2: CI/CD Quality Gate Command**
  * **Location:** `src/Console/Commands/CiAuditCheckCommand.php`
  * **Description:** Enable CI/CD pipeline integration (GitHub Actions, GitLab CI):
    * Command: `php artisan quinrel:ci-check --max-critical=0 --min-score=85`.
    * Exits with code `1` if critical errors (broken links, index blockers, critical a11y violations) exceed thresholds.

- [x] **Task 5.3: Executive Client Report Generator (HTML/PDF/CSV)**
  * **Location:** `src/Services/Reporting/ClientReportGenerator.php`, `resources/views/reports/executive.blade.php`
  * **Description:** Upgrade [`ClientReportGenerator`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Services/Reporting/ClientReportGenerator.php) to generate comprehensive client-ready audit reports incorporating all four pillars with executive summaries, charts, and prioritized remediation action plans.

---

## Definition of Done (DoD) for v1.2.0

A task in this backlog is considered **DONE** only when:
1. **Implementation is Complete:** Zero placeholders, TODOs, mock stubs, or fake returns.
2. **Architecture Aligns with `AGENTS.md`:** Laravel-native mechanisms used; no unnecessary external composer packages introduced.
3. **Security Standards Met:** All URL fetching passes through [`UrlValidator`](file:///Applications/XAMPP/xamppfiles/htdocs/quinrel-laravel/src/Support/UrlValidator.php) (SSRF prevention); dashboard/API routes protected by Gate authorization (`view-quinrel`, `manage-quinrel`).
4. **Reliability & Idempotency:** Automated fixers and queue jobs are idempotent; retries do not produce duplicate records or infinite loops.
5. **Quality Assurance:**
   * Unit and Feature tests written and passing for all new services and commands.
   * `vendor/bin/phpstan analyse` passes at Level 8 without baseline suppressions.
   * `vendor/bin/pint --test` passes with clean code formatting.
6. **Documentation Updated:** `README.md`, config docblocks, and command help text accurately reflect new capabilities.

