import os

filepath = '/Applications/XAMPP/xamppfiles/htdocs/holvek-laravel/TASKS.md'

with open(filepath, 'r') as f:
    content = f.read()

indexnow = "- [ ] **IndexNow Queueing (Rate Limit Risk):** Implement Laravel Job Batching or a delayed dispatcher for IndexNow to group bulk URL updates into a single ping, avoiding IP bans from search engines.\n"
gsc_url = "- [ ] **GSC URL Submission:** Integrate the Google Indexing API to submit content updates directly to the linked GSC property.\n"
gsc_sitemap = "- [ ] **GSC Sitemap Submission:** Automatically submit the generated `sitemap.xml` to the linked GSC property via API upon generation or major update.\n"
gsc_analytics = "- [ ] **GSC Analytics:** Implement OAuth flow to fetch GSC metrics (Clicks, Impressions, CTR) directly in the dashboard.\n"
gsc_index = "- [ ] **GSC Index Status:** Integrate the GSC URL Inspection API so publishers can view the exact index status (Indexed, Discovered, Crawled but not indexed) of any model directly in the Laravel dashboard without logging into GSC.\n"
cloud_lighthouse = "- [ ] **Cloud API Integration:** Offload resource-intensive Lighthouse Auditing to the Holvek Cloud API, executing via queued jobs and receiving results via webhooks.\n"
cloud_webhook = "- [ ] **Webhook Security (Spoofing Risk):** Implement strict HMAC SHA256 cryptographic signature verification on all incoming webhooks from Holvek Cloud.\n"

content = content.replace(indexnow, "")
content = content.replace(gsc_url, "")
content = content.replace(gsc_sitemap, "")
content = content.replace(gsc_analytics, "")
content = content.replace(gsc_index, "")
content = content.replace(cloud_lighthouse, "")
content = content.replace(cloud_webhook, "")

content = content.replace("## Phase 6: Content Analysis & AI (v1) 🤖", "## Phase 9: AI & AEO Generative APIs 🤖")
content = content.replace("## Phase 7: Polish, Documentation & Release 🚀", "## Phase 10: Polish, Documentation & Release 🚀")

new_phases = f"""## Phase 6: Google Search Console API 📈
**Goal:** Deep integration with Google Search Console for analytics and indexing management.
{gsc_url}{gsc_sitemap}{gsc_analytics}{gsc_index}
## Phase 7: Bing & IndexNow APIs 🚀
**Goal:** Direct ping and index management for Bing and IndexNow compatible search engines.
{indexnow}
## Phase 8: Performance, Quality & Cloud APIs ☁️
**Goal:** Offload resource-intensive auditing tasks to the Holvek Cloud API.
{cloud_lighthouse}{cloud_webhook}
"""

content = content.replace("## Phase 9: AI & AEO Generative APIs 🤖", new_phases + "## Phase 9: AI & AEO Generative APIs 🤖")

with open(filepath, 'w') as f:
    f.write(content)

print("Tasks updated.")
