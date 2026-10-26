# Operations & Support Runbook

This runbook provides actionable troubleshooting and operational maintenance procedures for `quinrel/laravel` in production and high-scale enterprise environments.

---

## 1. Routine Maintenance & Artisan Commands

### Sitemap Generation
To regenerate sitemaps on demand or schedule them via Laravel's Task Scheduler:

```bash
# Generate XML sitemaps to the public disk
php artisan quinrel:generate-sitemaps

# Stream to Amazon S3 or Cloudflare R2
php artisan quinrel:generate-sitemaps --disk=s3 --news --video
```

### Technical SEO Crawl & Health Check
Run site-wide streaming audits with concurrency control:

```bash
php artisan quinrel:crawl --limit=500 --include-models
```

### Static Accessibility Audits
Audit any URL against WCAG 2.1/2.2 AA standards:

```bash
php artisan quinrel:audit-a11y https://example.com
```

### CI/CD Quality Gate
Verify pull requests and builds against quality thresholds:

```bash
php artisan quinrel:ci-check --url=https://example.com --max-critical=0 --min-score=85
```

---

## 2. Common Issues & Remediation

### Missing Assets on Dashboard (`/quinrel`)
- **Symptom:** The standalone dashboard displays a blank page or 404 on Vite build assets.
- **Remediation:** Publish or republish package frontend assets:
  ```bash
  php artisan vendor:publish --tag="quinrel-assets" --force
  ```

### Access Denied (403 Forbidden) on Dashboard
- **Symptom:** Navigating to `/quinrel` returns HTTP 403.
- **Cause:** In non-local environments, the `view-quinrel` and `manage-quinrel` gates require authorization.
- **Remediation:** Define the gate in your `AppServiceProvider`:
  ```php
  use Illuminate\Support\Facades\Gate;

  Gate::define('view-quinrel', function ($user = null) {
      return $user && in_array($user->email, config('quinrel.authorized_emails', []));
  });
  ```

### SSRF Protection Rejections (`UrlValidatorException`)
- **Symptom:** Outbound crawl or webhook calls throw an SSRF exception.
- **Cause:** Quinrel blocks requests pointing to loopback (`127.0.0.1`, `localhost`), RFC 1918 private subnets (`10.0.0.0/8`, `192.168.0.0/16`, `172.16.0.0/12`), and AWS/GCP cloud metadata IPs (`169.254.169.254`).
- **Remediation:** Ensure URLs tested during audits resolve to valid public hostnames.

### Google Search Console API Quota Exceeded
- **Symptom:** `GscIndexStatus` logs rate limit errors (`429 Too Many Requests`).
- **Remediation:** Quinrel bounds inspection calls and caches results. Ensure `QUINREL_GSC_SERVICE_ACCOUNT` is configured with an active Google Cloud Project with the Search Console API enabled.

### IndexNow Key Verification Failure
- **Symptom:** Bing or IndexNow API reports key verification failure.
- **Remediation:** Verify that the key file is publicly accessible:
  ```bash
  curl -I https://your-domain.com/{QUINREL_INDEXNOW_KEY}.txt
  ```
  Ensure `quinrel:install` has placed the key in `.env` and `config('quinrel.indexnow.key')` matches.

