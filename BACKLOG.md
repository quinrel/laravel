# v1.0.0 Stabilization Backlog

This backlog outlines the final engineering tasks required to bring the `holvek-laravel` package to a production-ready `v1.0.0` state. All core features (Phases 1-13) are implemented; this backlog strictly focuses on configuration mapping, provider wiring, test coverage, and documentation.

## Epic 1: Configuration Parity
**Goal:** Ensure all newly developed services have corresponding configuration keys with safe defaults.

- [ ] **Task 1.1:** Expand `config/holvek.php` with the `analytics` array (`ga4_property_id`, `ga4_access_token`).
- [ ] **Task 1.2:** Expand `config/holvek.php` with the `api` array (`google_pagespeed_key`, `serpapi_key`, `indexnow_key`, `google_oauth_token`, `cloud_key`, `webhook_secret`).
- [ ] **Task 1.3:** Expand `config/holvek.php` with the `ai` array (`provider`, `ollama_url`, `ollama_model`, `openai_key`).
- [ ] **Task 1.4:** Expand `config/holvek.php` with the `defaults` and `social` arrays (`og_image`, `twitter`).
- [ ] **Task 1.5:** Document environment variables within the config file stub so users know what to place in `.env`.

## Epic 2: Service Provider Registration
**Goal:** Expose Blade components and package singletons properly to the host application.

- [ ] **Task 2.1:** Register `<x-holvek-cookie-notice>` Blade component in `HolvekServiceProvider::boot()`.
- [ ] **Task 2.2:** Register `<x-holvek-ai-disclosure>` Blade component in `HolvekServiceProvider::boot()`.
- [ ] **Task 2.3:** Map and verify the HTML Sitemap component namespace/alias.
- [ ] **Task 2.4:** Ensure `ClientReportGenerator`, `KeywordCannibalizationDetector`, and AI Services are registered as Singletons where beneficial.

## Epic 3: Core Test Suite (Quality Assurance)
**Goal:** Provide baseline automated test coverage for critical package boundaries to prevent regressions.

- [ ] **Task 3.1: Technical SEO Tests**
  - [ ] Write `BrokenLinkCheckerTest` using `Http::fake()` to verify chunking and timeout behavior without hitting the network.
  - [ ] Write `KeywordCannibalizationDetectorTest` using an SQLite memory database to seed `HolvekMeta` records and assert native SQL aggregation.
- [ ] **Task 3.2: Analytics & Tracking Tests**
  - [ ] Write `KeywordRankTrackerTest` with `Http::fake()` for `SerpApiClient` to assert ranking arrays map correctly.
  - [ ] Write `GscClientTest` to verify correct payload construction for Google Search Console API.
- [ ] **Task 3.3: AI Generative Tests**
  - [ ] Write `AiWritingAssistantTest` mocking OpenAI and Ollama HTTP endpoints.
- [ ] **Task 3.4: File/Streaming Tests**
  - [ ] Write `SitemapGeneratorTest` using `Storage::fake()` to ensure XML generation and chunking works under `lazy()` iteration.

## Epic 4: Documentation & Developer Experience
**Goal:** Prepare public-facing documentation for package consumers.

- [ ] **Task 4.1:** Rewrite `README.md` to reflect the completed `v1.0.0` features (13 phases).
- [ ] **Task 4.2:** Document Installation Steps (Composer require, vendor publish for config/migrations).
- [ ] **Task 4.3:** Document how to use `@holvekHead` and implement the `HasHolvekMeta` trait on Models.
- [ ] **Task 4.4:** Document usage of CLI tools and background queues/cron scheduling for the Tracking components.
- [ ] **Task 4.5:** Update `docs/features.md` if any deviations occurred during final implementation.

## Epic 5: Final Review & Release
**Goal:** Final CI pass and version tagging.

- [ ] **Task 5.1:** Setup `.github/workflows/tests.yml` to automatically run `phpstan`, `pint`, and `phpunit`.
- [ ] **Task 5.2:** Perform a final clean run of `vendor/bin/pint`, `vendor/bin/phpstan analyse`, and `vendor/bin/phpunit`.
- [ ] **Task 5.3:** Tag the repository `v1.0.0` and deploy release notes.
