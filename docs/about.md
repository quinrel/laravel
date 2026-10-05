# About Holvek

Holvek is an all-in-one search engine optimization (SEO) and discovery intelligence package for Laravel. It helps website owners, content creators, and businesses make their content easy to find on search engines like Google and Bing, as well as modern AI-powered search tools like ChatGPT, Claude, and Perplexity.

---

## Why We Built Holvek
The current change in the search landscape has made traditional SEO tools grossly inadequate. 
Businesses want to focus on their core operations and drive growth rather than juggling between multiple tools to reach out to customers. 
The majority of SEO tools are bloated and require some level of expertise to get meaningful results.

Hence, we built Holvek to help businesses reach their clients and customers wherever they are. Holvek strives to bring all your SEO and discoverability needs into a single dashboard that doesn't just help you understand and monitor your discoverability—it hands you ready-to-use fixes to improve discovery. 

Search has also changed. Today, people discover websites not only through blue links on Google, but through AI overviews, chatbots, social media answer engines, and mobile assistants.

Holvek was built to solve these problems with three simple ideas:

1. **Keep it lightweight:** An SEO package should never slow down your application. We keep our code clean, run heavy tasks in the background using Laravel Queues, and add minimal overhead to your pages.
2. **Make SEO easy to understand:** We explain recommendations in plain English without confusing acronyms or buzzwords.
3. **Be ready for both search engines and AI:** We help you structure your content so that both search engine bots and AI tools can read, understand, and cite your work accurately.

---

## Who Is Holvek For? - Built for Every User

Our goal is simple: to provide discoverability intelligence and fixes to every site owner without increased workload or juggling through multiple tools.

The free tier is available to everyone and competes favorably well with your favorite paid traditional SEO packages.

Good tools should have no barriers. We believe the free tier is good enough to get you started while the paid tier unlocks the full discovery intelligence capabilities of Holvek Cloud.
- **Content Creators:** Write better content with real-time suggestions for headings, readability, and key phrases directly via our API and Dashboard.
- **News Publishers & Media Sites:** Reach audiences quickly with Google News sitemaps, live article schema, and instant indexing via IndexNow.
- **Small Businesses & Local Services:** Help customers find your physical address, phone number, opening hours, and service areas with automated Local Business schema.
- **eCommerce Stores:** Highlight your products, prices, and stock availability directly in Google search results.
- **Agencies & Editorial Teams:** Manage multiple team members safely with custom user roles, Laravel Authorization Gates, granular permissions, and a clear audit log of all SEO changes.

---

## How It Works

### Smart Defaults Right Out of the Box
The moment you install Holvek, it gets to work. The package automatically creates XML sitemaps, sets up standard Open Graph tags for social sharing, generates breadcrumbs, and adds valid Schema.org structured data. You do not need to configure dozens of complicated settings just to get started.

### Helpful On-Page Writing Guidance
When managing content through your application, Holvek provides practical, actionable advice:
- Checks whether your title and description fit comfortably on mobile and desktop screens.
- Evaluates text readability using the established Flesch-Kincaid scale.
- Shows live previews of how your content will look on Google, Facebook, and Twitter/X.
- Suggests links to other relevant articles on your site to keep readers engaged.

### Search Engine Notifications
Holvek can use IndexNow to notify participating search engines about published URLs when enabled. Google’s Indexing API is restricted to eligible JobPosting and livestream pages; neither notification guarantees indexing.

### Built-in Technical Tools
You don't need five different packages to manage basic website health. Holvek includes:
- A redirect manager for 301 and 302 redirects built as Laravel middleware.
- A 404 error monitor to catch and fix broken URLs.
- A dynamic robots.txt and llms.txt generator.
- A broken link checker that runs as a background queued job to find dead hyperlinks in your content.

### Clear Performance & Search Insights
Connect Google Search Console and Google Analytics 4 to see real traffic numbers, search clicks, and top-ranking keywords directly inside your dashboard.

### Optional AI Assistance (v1)
If you want help writing titles, meta descriptions, or summaries, you can connect your preferred AI provider (such as OpenAI, Anthropic, or Ollama). A remote Ollama host receives the text you submit. AI features are completely optional—the package works fully without any third-party AI keys.

---

## Our Core Guarantees

- **No Bloat:** We don't display intrusive banner ads, upsell popups, or unnecessary widgets in your dashboard.
- **Zero Lock-In:** Your data belongs to you. All configurations and SEO metadata are securely stored in your own Laravel database.
- **Privacy-First:** We respect visitor privacy. Holvek does not perform any unauthorized tracking or insert third-party analytics scripts without your consent.
- **Open Standards:** Everything we generate adheres to official web standards, including Schema.org JSON-LD specifications, Google Search documentation, and emerging AI standards like `llms.txt`.
