# Holvek - Visibility & Discovery Intelligence

**Holvek** is a Laravel-native visibility and discovery intelligence toolkit that helps businesses understand their visibility across discovery surfaces (search engines, social media, answer engines, maps, directories, etc.) and helps them take action.

This toolkit provides powerful Search Engine Optimization (SEO) and Generative Engine Optimization (GEO) capabilities, optimized to compete excellently with paid versions of your favorite SEO tools. Identify critical technical SEO issues and improve your website's health, performance, and discoverability with automated audits, real-time monitoring, and actionable insights.

The Holvek Cloud-connected tier gives you monumental intelligence and hands you quick fixes to improve your brand discoverability wherever your audience is.

Holvek operates via a highly optimized React SPA dashboard and communicates exclusively through secure API endpoints within your Laravel application.

## Core Features

### Visibility
* Search visibility
* AI Visibility
* Social Visibility
* Discovery coverage
* Discovery Monitoring
* Brand/entity presence
* Local visibility
* Search Console signals
* Indexing status

### Intelligence
* Visibility opportunities
* Missing signals
* Competitor observations
* Search/discovery issues
* Historic intelligence
* Recommendations

### Diagnosis
* Technical Audit
* Discovery Audit
* Social Audit
* Holscan

### Technical SEO
* Schema (JSON-LD)
* Metadata (Title, Description, OpenGraph, Twitter Cards)
* XML/HTML Sitemaps (News, Standard)
* Robots.txt & llms.txt
* IndexNow API integration
* Redirect management
* Canonical URLs
* Indexing controls
* Internal linking
* Image & Video SEO
* Content/site analysis

### Connections
* Holvek Cloud
* Google Search Console
* Bing Webmaster Tools
* AI Providers (OpenAI, Anthropic, Ollama)

---

## Installation & Setup

1. Install the package via Composer:
   ```bash
   composer require holvek/laravel
   ```

2. Publish the configuration and assets:
   ```bash
   php artisan vendor:publish --tag="holvek-config"
   php artisan vendor:publish --tag="holvek-assets"
   ```

3. Run the database migrations:
   ```bash
   php artisan migrate
   ```

## Authentication & Cloud Connectivity

Connecting your Laravel application to Holvek Cloud operates via a secure handshake:
1. Connect site via the dashboard.
2. Holvek Cloud issues a site-specific credential.
3. The package stores it securely in your database or `.env`.
4. Scoped permissions apply with token rotation, revocation, and API rate limits.

---

## Architecture Overview

**Git repository:** `holvek-laravel`
**Package slug:** `holvek`
**PHP namespace:** `Holvek\Laravel`

```text
src/
├── Console/         # Artisan commands (e.g., audits, pruning)
├── Http/            # Controllers, Middleware, API endpoints
├── Models/          # Eloquent models (Meta, Redirects, Logs)
├── Providers/       # Service providers
├── Services/        # Core logic (Crawler, Schema, Sitemap)
└── View/            # Blade directives (@holvekHead)

resources/
├── js/              # React SPA dashboard source
└── views/           # Blade templates
```
