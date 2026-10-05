# Third-Party Services & Data Disclosures

Holvek operates under a strict data minimization philosophy. The package itself does **not** collect telemetry, track users, or "phone home" to our servers for general usage statistics.

However, to provide advanced SEO functionality, Holvek acts as a bridge to several external APIs. These APIs are entirely optional and are only invoked if a Site Administrator explicitly configures them.

## Services That May Receive Data

| Feature | External Service | Data Transmitted | Privacy Policy |
| :--- | :--- | :--- | :--- |
| **LLM Writing Assistant (v1)** | OpenAI API (api.openai.com) | When explicitly triggered by an editor, the current title, meta description, or content excerpts are sent to generate suggestions. | [OpenAI Privacy](https://openai.com/privacy) |
| **LLM Writing Assistant (v1)** | Anthropic API (api.anthropic.com) | Same as above, if Anthropic is selected instead of OpenAI. | [Anthropic Privacy](https://www.anthropic.com/privacy) |
| **Rank Tracking** | SerpApi (serpapi.com) | Your configured focus keywords are sent to retrieve SERP rankings. | [SerpApi Privacy](https://serpapi.com/privacy) |
| **PageSpeed Dashboard** | Google PageSpeed API | The URLs of your routes/models are transmitted to fetch Core Web Vitals. | [Google Privacy](https://policies.google.com/privacy) |
| **Search Console** | Google Search Console API | Read-only OAuth2 access is used to fetch impressions, clicks, and CTR data. No data is written to Google. | [Google Privacy](https://policies.google.com/privacy) |
| **Instant Indexing** | IndexNow (`api.indexnow.org`, Bing, Yandex, Seznam) | After an administrator turns IndexNow on and saves a key, published URLs are queued and sent in small batches only to those allow-listed endpoints. Holvek does not create a key, and nothing is sent synchronously during a web request. | [IndexNow Privacy](https://www.indexnow.org/) |
| **Instant Indexing** | Google Indexing API | If enabled, published URLs are sent to Google to accelerate crawling. | [Google Privacy](https://policies.google.com/privacy) |
| **Holvek Cloud** | Holvek (api.holvek.com) | If you connect Holvek Cloud, requests go only to `https://api.holvek.com`. The configuration cannot change that host. | [Holvek Privacy](https://holvek.com/privacy) |

## Data Retention & Storage
- **Stored credentials:** API keys, access tokens, and the Holvek license string are securely stored using Laravel's native encryption in the `.env` file, `config/holvek.php`, or encrypted within the `holvek_settings` table. 
- **Ollama:** The default address is `http://localhost:11434` on the host server, and that traffic stays on the machine. If an administrator confirms a remote http or https host, Holvek sends the prompt (which can include content excerpts) to that host. 
- **404 Logs:** Missing URLs are logged in your local `holvek_404_logs` table to assist in redirect creation. By default, these logs are automatically pruned after 90 days via Laravel Scheduled Tasks.
- **Audit Logs:** SEO setting changes are recorded in the `holvek_change_logs` table for accountability. These are automatically pruned after 365 days.
