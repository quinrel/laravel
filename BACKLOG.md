# v1.0.0 Stabilization Backlog

This backlog outlines the final engineering tasks required to bring the `quinrel-laravel` package to a production-ready `v1.0.0` state. All core features (Phases 1-13) are implemented; this backlog strictly focuses on configuration mapping, provider wiring, test coverage, and documentation.

## Epic 1: Configuration Parity
**Goal:** Ensure all newly developed services have corresponding configuration keys with safe defaults.

- [x] **Task 1.1:** Expand `config/quinrel.php` with the `analytics` array (`ga4_property_id`, `ga4_access_token`).
- [x] **Task 1.2:** Expand `config/quinrel.php` with the `api` array (`google_pagespeed_key`, `serpapi_key`, `indexnow_key`, `google_oauth_token`, `cloud_key`, `webhook_secret`).
- [x] **Task 1.3:** Expand `config/quinrel.php` with the `ai` array (`provider`, `ollama_url`, `ollama_model`, `openai_key`).
- [x] **Task 1.4:** Expand `config/quinrel.php` with the `defaults` and `social` arrays (`og_image`, `twitter`).
- [x] **Task 1.5:** Document environment variables within the config file stub so users know what to place in `.env`.

## Epic 2: Service Provider Registration
**Goal:** Expose Blade components and package singletons properly to the host application.

- [x] **Task 2.1:** Register `<x-quinrel-cookie-notice>` Blade component in `QuinrelServiceProvider::boot()`.
- [x] **Task 2.2:** Register `<x-quinrel-ai-disclosure>` Blade component in `QuinrelServiceProvider::boot()`.
- [x] **Task 2.3:** Map and verify the HTML Sitemap component namespace/alias.
- [x] **Task 2.4:** Ensure `ClientReportGenerator`, `KeywordCannibalizationDetector`, and AI Services are registered as Singletons where beneficial.

## Epic 3: Core Test Suite (Quality Assurance)
**Goal:** Provide baseline automated test coverage for critical package boundaries to prevent regressions.

- [x] **Task 3.1: Technical SEO Tests**
  - [x] Write `BrokenLinkCheckerTest` using `Http::fake()` to verify chunking and timeout behavior without hitting the network.
  - [x] Write `KeywordCannibalizationDetectorTest` using an SQLite memory database to seed `QuinrelMeta` records and assert native SQL aggregation.
- [x] **Task 3.2: Analytics & Tracking Tests**
  - [x] Write `KeywordRankTrackerTest` with `Http::fake()` for `SerpApiClient` to assert ranking arrays map correctly.
  - [x] Write `GscClientTest` to verify correct payload construction for Google Search Console API.
- [x] **Task 3.3: AI Generative Tests**
  - [x] Write `AiWritingAssistantTest` mocking OpenAI and Ollama HTTP endpoints.
- [x] **Task 3.4: File/Streaming Tests**
  - [x] Write `SitemapGeneratorTest` using `Storage::fake()` to ensure XML generation and chunking works under `lazy()` iteration.

## Epic 4: Documentation & Developer Experience
**Goal:** Prepare public-facing documentation for package consumers.

- [x] **Task 4.1:** Rewrite `README.md` to reflect the completed `v1.0.0` features (13 phases).
- [x] **Task 4.2:** Document Installation Steps (Composer require, vendor publish for config/migrations).
- [x] **Task 4.3:** Document how to use `@quinrelHead` and implement the `HasQuinrelMeta` trait on Models.
- [x] **Task 4.4:** Document usage of CLI tools and background queues/cron scheduling for the Tracking components.
- [x] **Task 4.5:** Update `docs/features.md` if any deviations occurred during final implementation.

## Epic 5: Final Review & Release
**Goal:** Final CI pass and version tagging.

- [x] **Task 5.1:** Setup `.github/workflows/tests.yml` to automatically run `phpstan`, `pint`, and `phpunit`.
- [x] **Task 5.2:** Perform a final clean run of `vendor/bin/pint`, `vendor/bin/phpstan analyse`, and `vendor/bin/phpunit`.
- [x] **Task 5.3:** Tag the repository `v1.0.0` and deploy release notes.
