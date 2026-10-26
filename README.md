# Quinrel for Laravel

[![Tests](https://github.com/quinrel/laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/quinrel/laravel/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/quinrel/laravel.svg?style=flat-square)](https://packagist.org/packages/quinrel/laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/quinrel/laravel.svg?style=flat-square)](https://packagist.org/packages/quinrel/laravel)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)

An elite, full-suite SEO, Structured Data, Real-User Monitoring (RUM), and AI Crawling (AEO) architecture engineered specifically for modern Laravel applications (Laravel 11+ & PHP 8.2+).

---

## Highlights

* **Code-First & Eloquent SEO:** Fluent runtime overrides via `Quinrel` facade, route-level macros, and smart Eloquent attribute fallbacks with zero database requirement out-of-the-box.
* **Octane & Cloud-Native Ready:** Thread-safe scoped container bindings, bounded TTL caching, and cloud-compatible streaming sitemaps (AWS S3, Cloudflare R2, local).
* **Enterprise Security & Privacy (OWASP & GDPR):** Built-in SSRF protection (`UrlValidator`), access gates (`view-quinrel`), two-tier cookie consent notices, and zero-CDN first-party RUM telemetry.
* **Instant Search Engine Indexing:** Automated IndexNow API pings, Google URL Inspection API diagnostics & remediation plans, and Google Search Console metric harvesting.
* **Active Diagnostics & Autofix Engine:** Site-wide streaming SEO crawler (`quinrel:crawl`), Lighthouse/CWV opportunity plans, WCAG 2.1/2.2 AA static accessibility auditor (`quinrel:audit-a11y`), AI alt-text & metadata autofix commands, and CI/CD quality gates (`quinrel:ci-check`).
* **AI & GEO (Generative Engine Optimization) Toolkit:** Domain root `/llms.txt` and `/llms-full.txt` streaming endpoints, Markdown content negotiation (`quinrel.aeo` middleware & `<link rel="alternate" type="text/markdown">`), RFC 9309 AI Search vs. AI Training bot management in `RobotsTxtManager`, local GEO citability scoring in `SeoChecklist`, and Claude 3.5 / OpenAI / Ollama content generation.
* **Drop-In Filament & Standalone UI:** Includes Filament v3 resource fieldsets (`QuinrelMetaResource::getEmbeddedFormSchema()`), dedicated `CrawlDashboard`, diagnostic widgets, and an isolated standalone React dashboard.

---

## Installation

Install Quinrel via Composer:

```bash
composer require quinrel/laravel
```

Run the interactive setup command:

```bash
php artisan quinrel:install
```

Alternatively, publish configuration, migrations, and assets manually:

```bash
php artisan vendor:publish --tag="quinrel-config"
php artisan vendor:publish --tag="quinrel-migrations"
php artisan vendor:publish --tag="quinrel-assets"
```

Run the database migrations:

```bash
php artisan migrate
```

---

## Quickstart

### 1. In Your Master Layout

Place `@quinrelHead` in your `<head>` and `@quinrelRum` before the closing `</body>`:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @quinrelHead
</head>
<body class="font-sans antialiased">
    {{ $slot }}

    <x-quinrel-cookie-notice />
    @quinrelRum
</body>
</html>
```

### 2. Code-First Fluent Metadata

Set page metadata dynamically in your controllers or middleware:

```php
use Quinrel\Laravel\Facades\Quinrel;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        Quinrel::setTitle($product->name)
            ->setDescription($product->summary)
            ->setCanonical(route('products.show', $product))
            ->setOgImage($product->cover_image_url)
            ->setSchema([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $product->price,
                    'priceCurrency' => 'USD',
                ],
            ]);

        return view('products.show', compact('product'));
    }
}
```

### 3. Route Macros

Attach SEO metadata directly to routes (100% compatible with `php artisan route:cache`):

```php
Route::get('/pricing', PricingController::class)
    ->seo([
        'title' => 'Pricing Plans — Transparent and Fair',
        'description' => 'Explore pricing plans designed to scale with your team.',
        'canonical' => 'https://example.com/pricing',
    ]);
```

### 4. Eloquent Models with Smart Fallbacks

Add the `HasQuinrelMeta` trait to your models:

```php
use Illuminate\Database\Eloquent\Model;
use Quinrel\Laravel\Traits\HasQuinrelMeta;

class Post extends Model
{
    use HasQuinrelMeta;
}
```

When calling `@quinrelHead($post)`, Quinrel automatically falls back to `$post->title ?? $post->name`, extracts excerpts from `$post->body`, and pulls images without requiring a database record upfront.

---

## Sitemaps & Cloud Storage

Generate multi-chunked monthly XML sitemaps, Google News sitemaps, and Video sitemaps:

```bash
# Generate to default disk (public)
php artisan quinrel:generate-sitemaps

# Stream to AWS S3 or Cloudflare R2 with news and video indexes
php artisan quinrel:generate-sitemaps --disk=s3 --news --video
```

---

## 1-Click Competitor Migration

Seamlessly import existing titles, descriptions, and canonicals from competitor packages without data loss:

```bash
# Automatically detects source tables and migrates to quinrel_meta
php artisan quinrel:migrate-competitor ralphjsmit/laravel-seo
php artisan quinrel:migrate-competitor spatie/laravel-seo
php artisan quinrel:migrate-competitor artesaos/seotools
```

---

## Diagnostics, Autofix & CI/CD Quality Gates

Run full-site technical SEO crawls, accessibility audits, AI autofixes, and CI/CD pipeline quality checks directly via Artisan:

```bash
# Stream a site-wide technical SEO crawl and generate prioritized P0-P3 remediation tickets
php artisan quinrel:crawl --limit=500 --include-models

# Run a static WCAG 2.1/2.2 AA accessibility audit on any local or production URL
php artisan quinrel:audit-a11y https://example.com

# Execute the unified 4-pillar audit (Technical SEO, GSC Indexing, PageSpeed, Accessibility)
php artisan quinrel:audit-all https://example.com

# Enforce SEO, performance, and accessibility quality thresholds in CI/CD pipelines
php artisan quinrel:ci-check --url=https://example.com --max-critical=0 --min-score=85

# Automatically draft missing SEO titles & descriptions or image alt text using AI
php artisan quinrel:autofix-meta --empty-only
php artisan quinrel:autofix-alt-text --model="App\Models\Post"
```

### Performance & LCP Helpers

Preload critical LCP assets and optimize image rendering in Blade:

```blade
{{-- Preload hero LCP image in <head> --}}
@quinrelPreload('/images/hero.webp', 'image', 'image/webp')

{{-- Render CLS-safe, LCP-aware image component --}}
<x-quinrel::image src="/images/hero.webp" alt="Platform overview" width="1200" height="630" :hero="true" />
```

---

## Filament Integration

Embed the complete Quinrel SEO suite directly into any Filament Resource (`PostResource`, `ProductResource`, etc.):

```php
use Quinrel\Laravel\Filament\Resources\QuinrelMetaResource;

public static function form(Form $form): Form
{
    return $form->schema([
        // Your application fields...
        Forms\Components\TextInput::make('title')->required(),

        // Embedded Quinrel SEO Tab
        QuinrelMetaResource::getEmbeddedFormSchema(),
    ]);
}
```

---

## Security & Access Control

By default, the standalone dashboard (`/quinrel`) and content analysis APIs are protected by the `view-quinrel` and `manage-quinrel` Gates. Define authorized roles in your `AppServiceProvider` or `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('view-quinrel', function ($user) {
    return in_array($user->role, ['admin', 'editor', 'seo']);
});
```

---

## Testing & Quality Assurance

Run the test suite, static analysis, and code formatting:

```bash
composer test
composer analyse
composer format
```

---

## License

Quinrel for Laravel is open-sourced software licensed under the [MIT license](LICENSE.md).
