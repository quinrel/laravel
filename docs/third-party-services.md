# Third-Party Services & Data Disclosures

Quinrel operates under a strict data minimization philosophy. The package itself does **not** collect telemetry, track users, or "phone home" to our servers for general usage statistics.

However, to provide advanced SEO functionality, Quinrel acts as a bridge to several external APIs. These APIs are entirely optional and are only invoked if an administrator explicitly configures them.

If you utilize these features, you should disclose them in your application's Privacy Policy.

## Services That May Receive Data

| Feature | External Service | Data Transmitted | Privacy Policy |
| :--- | :--- | :--- | :--- |
| **LLM Writing Assistant** | OpenAI API (api.openai.com) | When explicitly triggered by an editor, the current post title, meta description, or content excerpts are sent to generate suggestions. | [OpenAI Privacy](https://openai.com/privacy) |
| **LLM Writing Assistant** | Anthropic API (api.anthropic.com) | Same as above, if Anthropic is selected instead of OpenAI. | [Anthropic Privacy](https://www.anthropic.com/privacy) |
| **Rank Tracking** | SerpApi (serpapi.com) | Your configured focus keywords are sent to retrieve SERP rankings. | [SerpApi Privacy](https://serpapi.com/privacy) |
| **PageSpeed Dashboard** | Google PageSpeed API | The URLs of your posts/pages are transmitted to fetch Core Web Vitals. | [Google Privacy](https://policies.google.com/privacy) |
| **Search Console** | Google Search Console API | Read-only OAuth2 access is used to fetch impressions, clicks, and CTR data. No data is written to Google. | [Google Privacy](https://policies.google.com/privacy) |
| **Instant Indexing** | IndexNow (`api.indexnow.org`, Bing, Yandex, Seznam) | After an administrator turns IndexNow on and saves a key, published URLs are queued and sent in small batches only to those allow-listed endpoints. Quinrel does not create a key, and nothing is sent during the publish request. | [IndexNow Privacy](https://www.indexnow.org/) |
| **Instant Indexing** | Google Indexing API | If enabled, published URLs are sent to Google to accelerate crawling. | [Google Privacy](https://policies.google.com/privacy) |
| **Quinrel API** | Quinrel (api.quinrel.com) | If you connect Quinrel, requests go only to `https://api.quinrel.com`. The configuration cannot change that host. | [Quinrel Privacy](https://quinrel.com/privacy) |

## Data Retention & Storage
- **Stored credentials:** API keys, access tokens, and credentials are configured via standard Laravel environment variables and configuration files (`config/quinrel.php`).
- **Ollama:** The default address is `http://localhost:11434` on this server, and that traffic stays on the machine. If an administrator configures a remote http or https host, Quinrel sends the prompt, which can include the post title and post content, to that host.
- **404 Logs:** Missing URLs are logged in your local database (`quinrel_404_logs`) to assist in redirect creation. By default, these logs are automatically purged after 90 days.
- **Audit Logs:** SEO setting changes are recorded in a local audit log (`quinrel_audit_logs`) for accountability. These are automatically purged after 365 days.
