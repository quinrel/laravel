# WordPress Plugin Reference

This document maps the architectural patterns and concepts from the legacy Holvek WordPress plugin to their modern Laravel equivalents in this package.

## Core Architecture Mapping

| WordPress Concept | Laravel Package Equivalent | Notes |
| :--- | :--- | :--- |
| `add_menu_page()` | Route + Controller + SPA | The admin dashboard is now a precompiled React SPA served via a dedicated package route. Access is controlled via Authorization Gates. |
| Options Table (`wp_options`) | Config & Eloquent | Global API keys and environment-specific settings are in `.env` and `config/holvek.php`. User-editable settings from the dashboard are stored in a dedicated `holvek_settings` table. |
| WP-Cron | Laravel Queues & Horizon | Scheduled tasks (like technical audits and sitemap generation) now use Laravel's native Task Scheduling (`Console/Kernel.php`) and queued jobs. |
| Post Meta (`wp_postmeta`) | Eloquent Relationships | SEO metadata, social sharing tags, and structured data are stored in a `holvek_meta` table, linked to the host application's models via a Polymorphic Relationship (`morphMany` or `morphOne`). |
| Custom Post Types (CPTs) | Standard Eloquent Models | Features like Redirects and 404 Monitors are standard Eloquent models. |
| `add_action('wp_head', ...)` | Blade Directives | The package provides a `@holvekHead` Blade directive for users to drop into their layout files, ensuring the `<head>` is correctly populated. |

## Data Migration Considerations

Currently, there is no direct database migration tool from the WordPress plugin to this Laravel package, as the host environments are entirely different. Host applications adopting this package will start with a fresh schema.

## Deprecated WP-Specific Features
- **Kilo / WP Updater**: The bespoke `.kilo` updater and WordPress plugin update mechanisms are removed entirely. Updates are now handled natively via `composer update`.
- **Admin Notices**: Replaced by standard SPA notifications and Laravel session flashes.
- **WP REST API Endpoints**: Replaced by standard Laravel API routes under the `routes/api.php` file, specifically prefixed for the package (e.g., `/api/holvek/...`).

## AI Integration Differences
- **WordPress**: Mixed direct API calls to OpenAI with other services.
- **Laravel (v1)**: Direct integration with OpenAI/Anthropic/Ollama remains available as part of the standalone toolkit.
- **Laravel (v2)**: AI requests will be routed through the Holvek Cloud API for centralized rate limiting and processing.
