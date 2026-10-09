# Changelog

All notable changes to `holvek/laravel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-10-09

### Added
- **Redirect Management:** Infinite loop detection and redirect chain flattening capabilities in middleware and the Filament dashboard.
- **IndexNow API:** Automated ping logic to IndexNow endpoints (Bing, Yandex) upon SEO model creation or update, configurable via `holvek.indexnow`.
- **Internationalization (i18n):** Automated `hreflang` meta tag injection and support for `<xhtml:link>` elements in XML Sitemaps.
- **Google Search Console (GSC):** Dashboard integration displaying 30-day organic performance (clicks, impressions, position, CTR) site-wide and per-URL via a new artisan command (`holvek:fetch-gsc-data`).
- **Core Web Vitals (RUM):** Real-User Monitoring ingestion via the `@holvekRum` Blade directive and a high-throughput endpoint to measure LCP, INP, TTFB, FCP, and CLS directly from site visitors.

### Fixed
- Static analysis strict typing compliance across all new models and API services.

## [1.0.0] - Initial Release
- Basic SEO, Sitemap generation, and Head metadata management.

