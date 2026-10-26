# Headless REST API

Quinrel includes a lightweight, secure REST API designed for headless frontend architectures (Next.js, Nuxt, Astro, SvelteKit) and external monitoring integrations.

All endpoints are registered under the `/quinrel/api/v1` prefix and protected by standard Laravel rate limiting and middleware.

---

## Endpoints

### 1. Global SEO Configuration
Retrieve site-wide global SEO settings, defaults, social tags, and verification codes.

- **Method:** `GET`
- **URL:** `/quinrel/api/v1/seo/global`
- **Route Name:** `quinrel.api.seo.global`
- **Response:**
  ```json
  {
    "site_name": "My Laravel App",
    "title_separator": "—",
    "default_og_image": "https://example.com/images/default-og.png",
    "twitter_handle": "@quinrel",
    "noindex_non_production": true
  }
  ```

---

### 2. Model SEO Metadata
Fetch SEO metadata for a specific Eloquent model record by its `QuinrelMeta` ID.

- **Method:** `GET`
- **URL:** `/quinrel/api/v1/seo/{id}`
- **Route Name:** `quinrel.api.seo.show`
- **Parameters:**
  - `id` (integer, required): ID of the `QuinrelMeta` record.
- **Response:**
  ```json
  {
    "id": 1,
    "title": "Getting Started with Modern Laravel",
    "description": "Comprehensive guide to architectural best practices in Laravel 11.",
    "canonical_url": "https://example.com/posts/getting-started",
    "og_image": "https://example.com/images/hero.webp",
    "robots": "index, follow",
    "schema": {
      "@context": "https://schema.org",
      "@type": "Article",
      "headline": "Getting Started with Modern Laravel"
    }
  }
  ```

---

### 3. Discovery Snapshot Scan
Perform a fast, lightweight discovery and SEO scan of the host application.

- **Method:** `GET`
- **URL:** `/quinrel/api/v1/discovery/scan`
- **Route Name:** `quinrel.api.discovery.scan`
- **Rate Limit:** 60 requests / minute
- **Response:**
  ```json
  {
    "status": "success",
    "score": 92,
    "verdict": "pass",
    "indicators": {
      "robots_txt": true,
      "sitemap": true,
      "llms_txt": true,
      "canonical_coverage": 98.4
    }
  }
  ```

---

### 4. Real-User Monitoring (RUM) Ingestion
Collects real-user Core Web Vitals (LCP, INP, CLS, FCP, TTFB) submitted from browser performance observers.

- **Method:** `POST`
- **URL:** `/quinrel/api/v1/rum`
- **Route Name:** `quinrel.api.rum.store`
- **Rate Limit:** 600 requests / minute
- **Payload:**
  ```json
  {
    "session_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
    "url": "https://example.com/blog",
    "metrics": {
      "LCP": { "metric_name": "LCP", "metric_value": 1420.5, "rating": "good" },
      "INP": { "metric_name": "INP", "metric_value": 45.0, "rating": "good" },
      "CLS": { "metric_name": "CLS", "metric_value": 0.02, "rating": "good" }
    }
  }
  ```

---

### 5. Audit Webhooks
Receives asynchronous audit signals and triggers verification hooks.

- **Method:** `POST`
- **URL:** `/quinrel/api/v1/webhooks/audit`
- **Route Name:** `quinrel.webhooks.audit`

