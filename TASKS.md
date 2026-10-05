# Implementation Plan (TASKS.md)

This document outlines the phased execution plan for the `holvek-laravel` package. Each phase is designed to be independently verifiable and follows the architectural guidelines established in `AGENTS.md`.

## Phase 0: Foundation & Tooling 🏗️
**Goal:** Establish the repository structure, code quality tools, and Laravel package scaffolding.
- [ ] Initialize `composer.json` with correct Laravel 11/12/13 and PHP 8.2+ constraints.
- [ ] Configure code quality tooling:
  - [ ] Install and configure PHPUnit.
  - [ ] Install and configure Larastan (PHPStan for Laravel).
  - [ ] Install and configure Laravel Pint.
- [ ] Set up GitHub Actions CI workflow (tests, linting, static analysis).
- [ ] Create the core `HolvekServiceProvider`:
  - [ ] Register configuration publishing (`config/holvek.php`).
  - [ ] Register migrations.
  - [ ] Register package routes (`routes/web.php` and `routes/api.php`).
- [ ] Scaffold the React SPA dashboard:
  - [ ] Create a `resources/js` directory for the React app.
  - [ ] Configure Vite/Mix to compile the SPA to `dist/`.
  - [ ] Create the primary dashboard view and route.
  - [ ] Implement initial Authorization Gate for dashboard access.

## Phase 1: Core Metadata & Social (Head Component) 📝
**Goal:** Allow users to define and render SEO metadata on their application models.
- [ ] Create the `holvek_meta` database migration (polymorphic table for SEO overrides).
- [ ] Create the `HasHolvekMeta` trait and `HolvekMeta` Eloquent model.
- [ ] Develop the `@holvekHead` Blade directive:
  - [ ] Build the rendering pipeline to output title, description, and canonical URLs.
  - [ ] Add OpenGraph and Twitter Cards tag rendering.
- [ ] Write Feature tests for the `@holvekHead` rendering pipeline.
- [ ] Build the SPA UI for editing metadata (API endpoints & React components).

## Phase 2: Redirects & 404 Monitor 🔀
**Goal:** Manage URL redirects and track 404 errors.
- [ ] Create the `holvek_redirects` migration and Eloquent model.
- [ ] Implement the Redirect Middleware to intercept requests and redirect based on the database.
- [ ] Create the `holvek_404_logs` migration and Eloquent model.
- [ ] Implement the 404 logging logic (via Exception Handler or Fallback Route).
- [ ] Create artisan commands to prune old 404 logs.
- [ ] Build the SPA UI to view 404s and manage redirects.
- [ ] Write Feature tests verifying redirect status codes and 404 logging.

## Phase 3: Crawl Control & Sitemaps 🕷️
**Goal:** Manage robots.txt, llms.txt, and XML sitemaps.
- [ ] Implement the robots.txt and llms.txt generation endpoints.
- [ ] Create a configuration-driven sitemap generator:
  - [ ] Discover registered models and generate `sitemap.xml`.
  - [ ] Add support for sitemap indexes for large sites.
- [ ] Develop the SPA UI for configuring sitemap inclusions.
- [ ] Write Feature tests to validate XML schema and robots.txt syntax.

## Phase 4: Structured Data (Schema.org) 🧩
**Goal:** Provide a fluent API for generating JSON-LD structured data.
- [ ] Implement the Schema Generator service.
- [ ] Add support for common schemas (Article, Product, Organization, BreadcrumbList).
- [ ] Integrate Schema generation into the `@holvekHead` pipeline.
- [ ] Build the SPA UI to manage global schema settings (e.g., Organization details).
- [ ] Write Unit tests for JSON-LD generation.

## Phase 5: Technical Auditing & Link Health 🔍
**Goal:** Periodically scan the host application for SEO issues and broken links.
- [ ] Create the `holvek_change_logs` migration (formerly audit logs) for system events.
- [ ] Implement the internal Crawler Service:
  - [ ] Scan internal pages for broken links, missing H1s, and missing meta descriptions.
- [ ] Create scheduled Queued Jobs to run technical audits in the background.
- [ ] Build the SPA UI to display audit results and health scores.
- [ ] Write Feature tests for the crawler service using HTTP fakes.

## Phase 6: Content Analysis & AI (v1) 🤖
**Goal:** Provide AI-assisted SEO recommendations and content generation.
- [ ] Create API integrations for OpenAI, Anthropic, and Ollama.
- [ ] Implement the Content Analyzer service (e.g., keyword density, readability).
- [ ] Build the SPA UI to trigger AI suggestions for meta descriptions and titles.
- [ ] Write Contract tests for AI service adapters using mock responses.

## Phase 7: Polish, Documentation & Release 🚀
**Goal:** Finalize the package for public consumption.
- [ ] Conduct a full security review (Gates, SQL injection, API rate limits).
- [ ] Optimize database queries and eager loading to prevent N+1 issues.
- [ ] Write comprehensive installation and usage documentation (update `README.md`).
- [ ] Ensure 100% test coverage on critical paths.
- [ ] Tag the `v1.0.0` release.
