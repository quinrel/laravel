# Holvek Laravel

An elite, full-suite SEO & technical architecture package for Laravel 11+.

## Features (v1.0.0)
- **Local-First SEO Meta:** Fully dynamic `@holvekHead` component, route macros, and automatic canonicals.
- **On-Page SEO Tools:** Real-time keyword checklist, readability checker, and internal link suggestions.
- **Sitemaps & Crawling:** Memory-safe streaming XML sitemap generators (supports 1M+ URLs).
- **Technical SEO Audit:** Broken link checker, 404 monitor, regex-powered redirects, and orphaned content indexer.
- **AI Generative Writing:** Suggests SEO titles and descriptions using Ollama (local), OpenAI, or Anthropic.
- **Google Search Console & GA4:** Native integration for URL submission, index monitoring, and traffic reporting natively inside Laravel.

## Installation

You can install the package via composer:

```bash
composer require holvek/laravel
```

Publish the configuration, assets, and migrations:

```bash
php artisan vendor:publish --tag="holvek-config"
php artisan vendor:publish --tag="holvek-migrations"
```

Run the migrations:

```bash
php artisan migrate
```

## Basic Usage

### Configuring Global SEO
Update your `.env` file with the keys specified in `config/holvek.php`, particularly if you intend to use the AI, Google Analytics, or IndexNow features.

### Route Macros
Attach SEO metadata natively to any route:
```php
Route::get('/about', fn () => view('about'))->seo([
    'title' => 'About Us',
    'description' => 'The premier SEO package for Laravel.',
]);
```

### Models & Dynamic Metadata
Implement the `HasHolvekMeta` trait on your Eloquent models:
```php
use Holvek\Laravel\Traits\HasHolvekMeta;

class Post extends Model
{
    use HasHolvekMeta;
}
```

### The Blade Directive
Inject the compiled SEO headers into your master layout `<head>`:
```blade
<head>
    @holvekHead($model)
</head>
```

## Advanced Tooling

### Rank Tracking
Automatically check SerpApi for your keyword positions:
```php
app(\Holvek\Laravel\Services\Tracking\KeywordRankTracker::class)->trackAllKeywords();
```

### Keyword Cannibalization
Detect pages competing for the same target keyword natively via SQL aggregation:
```php
$duplicates = app(\Holvek\Laravel\Services\Analytics\KeywordCannibalizationDetector::class)->detect();
```

## Security
If you discover any security related issues, please email security@holvek.com instead of using the issue tracker.
