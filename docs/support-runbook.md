# Holvek — Support & Operations Runbook

## 1. Architecture Overview
Holvek is built as a Laravel-native visibility and discovery intelligence package.
- **Frontend**: React-based SPA located in `resources/js` and served via dedicated package routes.
- **Backend API**: Secure API endpoints under the `api/holvek` route prefix.
- **Queueing**: Relies on Laravel Queues (database, redis, sqs) for background tasks (e.g. Broken Link checks, IndexNow pinging).

## 2. Common Issues & Triage

### 2.1. React Dashboard Won't Load (Blank Screen)
- **Symptom**: Accessing the Holvek dashboard route shows a white screen.
- **Cause**: Usually a JavaScript error or capability mismatch.
- **Runbook**:
  1. Open Browser DevTools Console. Look for React crashes or 403 Forbidden errors.
  2. If 403 on `/api/holvek/...`, ensure the user passes the Laravel Authorization Gate defined in the package configuration.
  3. Verify that the SPA assets have been compiled and published properly (`php artisan vendor:publish`).

### 2.2. IndexNow / Background Jobs Not Firing
- **Symptom**: Content is published but search engines are not notified, or broken link checks do not complete.
- **Cause**: Laravel Queue workers are not running, or jobs are failing.
- **Runbook**:
  1. Check the `failed_jobs` table in the database or view Horizon if installed.
  2. Ensure the queue worker is running (`php artisan queue:work` or via Supervisor/Horizon).
  3. Verify that the scheduled task runner (`php artisan schedule:run`) is configured in the server's crontab.

### 2.3. Google Search Console Shows "Unconfigured"
- **Symptom**: Dashboard shows an error about OAuth token missing.
- **Cause**: The OAuth 2.0 access token expired and the refresh token was revoked, or credentials were deleted.
- **Runbook**: 
  1. Go to Settings -> Integrations in the dashboard.
  2. Disconnect and Re-authenticate the Google account.

### 2.4. PageSpeed Insights Timeout
- **Symptom**: Bulk checking CWV (Core Web Vitals) fails with 502 Bad Gateway.
- **Cause**: Google PageSpeed API rate limiting.
- **Runbook**: Wait 60 seconds and retry. Holvek falls back to Holvek Cloud infrastructure if connected, but Google's underlying API still applies strict per-minute quotas.

## 3. Database & Cleanup
To completely reset Holvek data:
1. Truncate the `holvek_settings` and `holvek_meta` tables.
2. Clear any cached configuration or routes (`php artisan optimize:clear`).
3. Flush any cache keys prefixed with `holvek:`.
