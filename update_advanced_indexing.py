import os

filepath = '/Applications/XAMPP/xamppfiles/htdocs/holvek-laravel/TASKS.md'
with open(filepath, 'r') as f:
    content = f.read()

# Phase 10 Update
old_p10 = """## Phase 10: Google Search Console API 📈
**Goal:** Deep integration with Google Search Console.
- [ ] **GSC URL Submission:** Integrate the Google Indexing API.
- [ ] **GSC Sitemap Submission:** Automatically submit `sitemap.xml`.
- [ ] **GSC Analytics:** Fetch GSC metrics (Clicks, Impressions, CTR) natively.
- [ ] **GSC Index Status:** View exact index status using the URL Inspection API."""

new_p10 = """## Phase 10: Google Search Console API 📈
**Goal:** Deep integration with Google Search Console for advanced index tracking.
- [ ] **GSC URL Submission:** Integrate the Google Indexing API.
- [ ] **GSC Sitemap Sync & Delete:** Automatically submit `sitemap.xml`, and add capability to **delete** submitted sitemaps via API.
- [ ] **Sitemap Drift Tracker:** Unified dashboard comparing what Holvek declared vs what Google actually discovered to identify indexing drift.
- [ ] **Daily URL Monitoring:** Scheduled cron to track daily indexing and HTTP status changes for critical URLs.
- [ ] **GSC Analytics:** Fetch GSC metrics (Clicks, Impressions, CTR) natively.
- [ ] **GSC Index Status:** View exact index status using the URL Inspection API."""

content = content.replace(old_p10, new_p10)

# Phase 11 Update
old_p11 = """## Phase 11: Bing, IndexNow & LLM Indexing APIs 🚀
**Goal:** Instant search engine indexing submission.
- [ ] **IndexNow Integration:** Batch notifications for Bing, Yandex, and Seznam.
- [ ] **LLM Indexing:** Ping ChatGPT, Claude, Gemini, and Perplexity on updates.
- [ ] **Indexing Dashboard:** View submission logs, daily quota usage, and response statuses."""

new_p11 = """## Phase 11: Bing, IndexNow & LLM Indexing APIs 🚀
**Goal:** Instant search engine indexing submission and cross-engine synchronization.
- [ ] **IndexNow Integration:** Batch notifications for Bing, Yandex, and Seznam.
- [ ] **Bing Indexing Status:** Check exact URL indexing status via the Bing Webmaster API.
- [ ] **Sync & Delete Sitemaps:** Synchronize sitemaps across Bing/Yandex, with the ability to delete them remotely via Webmaster APIs.
- [ ] **LLM Indexing:** Ping ChatGPT, Claude, Gemini, and Perplexity on updates.
- [ ] **Indexing Dashboard:** View submission logs, daily quota usage, and response statuses."""

content = content.replace(old_p11, new_p11)

with open(filepath, 'w') as f:
    f.write(content)
print("Tasks Updated Successfully")
