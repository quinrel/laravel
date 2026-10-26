<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Technical;

use Generator;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Models\QuinrelRedirect;
use Quinrel\Laravel\Services\SeoRouteManager;
use Quinrel\Laravel\Support\UrlValidator;
use Throwable;

class SiteCrawlOrchestrator
{
    public function __construct(
        protected ?InternalLinkIndexer $linkIndexer = null,
        protected ?SeoRouteManager $routeManager = null,
    ) {
        $this->linkIndexer ??= app(InternalLinkIndexer::class);
        $this->routeManager ??= app(SeoRouteManager::class);
    }

    /**
     * Stream seed URLs from routes, QuinrelMeta records, and XML sitemaps using LazyCollection.
     *
     * @param  array<int, string>  $explicitUrls
     * @return LazyCollection<int, string>
     */
    public function discoverSeedUrls(int $limit = 500, bool $includeModels = false, array $explicitUrls = []): LazyCollection
    {
        return new LazyCollection(function () use ($limit, $includeModels, $explicitUrls): Generator {
            $seen = [];
            $count = 0;
            $baseUrl = rtrim((string) config('app.url', 'http://localhost'), '/');

            $emit = function (string $rawUrl) use (&$seen, &$count, $limit, $baseUrl): ?string {
                if ($count >= $limit) {
                    return null;
                }

                $normalized = $this->normalizeUrl($rawUrl, $baseUrl);
                if ($normalized === null || isset($seen[$normalized])) {
                    return null;
                }

                $seen[$normalized] = true;
                $count++;

                return $normalized;
            };

            foreach ($explicitUrls as $explicitUrl) {
                $url = $emit($explicitUrl);
                if ($url !== null) {
                    yield $url;
                }
                if ($count >= $limit) {
                    return;
                }
            }

            // 1. Active QuinrelMeta canonical URLs (streamed via lazy cursor)
            $metaQuery = QuinrelMeta::query()
                ->whereNotNull('canonical_url')
                ->select(['id', 'canonical_url', 'model_type', 'model_id']);

            foreach ($metaQuery->lazy(200) as $meta) {
                /** @var QuinrelMeta $meta */
                if (is_string($meta->canonical_url) && $meta->canonical_url !== '') {
                    $url = $emit($meta->canonical_url);
                    if ($url !== null) {
                        yield $url;
                    }
                    if ($count >= $limit) {
                        return;
                    }
                }

                if ($includeModels && $meta->model_type && class_exists($meta->model_type)) {
                    try {
                        $model = $meta->model;
                        if ($model !== null && method_exists($model, 'getUrl')) {
                            $modelUrl = $model->getUrl();
                            if (is_string($modelUrl) && $modelUrl !== '') {
                                $url = $emit($modelUrl);
                                if ($url !== null) {
                                    yield $url;
                                }
                                if ($count >= $limit) {
                                    return;
                                }
                            }
                        }
                    } catch (Throwable) {
                        // Skip unresolvable morph models
                    }
                }
            }

            // 2. Registered application GET routes without required route parameters
            foreach ($this->discoverRouteUrls($baseUrl) as $routeUrl) {
                $url = $emit($routeUrl);
                if ($url !== null) {
                    yield $url;
                }
                if ($count >= $limit) {
                    return;
                }
            }

            // 3. Generated XML Sitemaps on public disk
            foreach ($this->discoverSitemapUrls() as $sitemapUrl) {
                $url = $emit($sitemapUrl);
                if ($url !== null) {
                    yield $url;
                }
                if ($count >= $limit) {
                    return;
                }
            }
        });
    }

    /**
     * Execute a concurrent, memory-streamed crawl across discovered or explicit URLs.
     *
     * @param  array<int, string>  $explicitUrls
     * @return array{
     *     total_crawled: int,
     *     pages: array<string, array<string, mixed>>,
     *     duplicate_canonicals: array<string, array<int, string>>,
     *     orphaned_urls: array<int, string>,
     *     redirect_chains: array<int, array<string, mixed>>,
     *     redirect_loops: array<int, array<string, mixed>>
     * }
     */
    public function crawl(
        int $limit = 500,
        bool $includeModels = false,
        int $chunkSize = 10,
        array $explicitUrls = []
    ): array {
        $chunkSize = max(1, $chunkSize);
        $pages = [];
        $allLinks = [];
        $canonicalMap = [];
        $baseUrl = rtrim((string) config('app.url', 'http://localhost'), '/');

        $seedStream = $this->discoverSeedUrls($limit, $includeModels, $explicitUrls);

        foreach ($seedStream->chunk($chunkSize) as $chunk) {
            $safeBatch = [];
            foreach ($chunk as $url) {
                if (! UrlValidator::isSafe($url)) {
                    $pages[$url] = $this->buildBlockedPageResult($url);
                    $allLinks[$url] = [];

                    continue;
                }

                $safeBatch[] = $url;
            }

            if ($safeBatch === []) {
                continue;
            }

            /** @var array<string, Response|Throwable> $responses */
            $responses = Http::pool(function (Pool $pool) use ($safeBatch) {
                $requests = [];
                foreach ($safeBatch as $url) {
                    $requests[] = $pool->as($url)->timeout(10)->get($url);
                }

                return $requests;
            });

            foreach ($safeBatch as $url) {
                $response = $responses[$url] ?? null;
                $parsed = $this->analyzeResponse($url, $response, $baseUrl);
                $pages[$url] = $parsed;
                $allLinks[$url] = $parsed['internal_links'];

                if (is_string($parsed['canonical']) && $parsed['canonical'] !== '') {
                    $canonicalMap[$parsed['canonical']][] = $url;
                }
            }
        }

        $duplicateCanonicals = array_filter(
            $canonicalMap,
            fn (array $urls): bool => count($urls) > 1
        );

        $crawledUrls = array_keys($pages);
        $orphanedUrls = $this->linkIndexer
            ? $this->linkIndexer->findOrphanedContent($allLinks, $crawledUrls)
            : [];

        // If only 1 page was crawled (e.g. single root audit), it is not considered orphaned
        if (count($crawledUrls) <= 1) {
            $orphanedUrls = [];
        }

        $redirectDiagnostics = $this->collectRedirectDiagnostics($pages);

        return [
            'total_crawled' => count($pages),
            'pages' => $pages,
            'duplicate_canonicals' => $duplicateCanonicals,
            'orphaned_urls' => $orphanedUrls,
            'redirect_chains' => $redirectDiagnostics['chains'],
            'redirect_loops' => $redirectDiagnostics['loops'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function analyzeResponse(string $url, mixed $response, string $baseUrl): array
    {
        if (! $response instanceof Response) {
            $errorMsg = $response instanceof Throwable ? $response->getMessage() : 'Request failed.';

            return [
                'url' => $url,
                'status' => 0,
                'redirect_to' => null,
                'title' => null,
                'title_length' => 0,
                'description' => null,
                'description_length' => 0,
                'canonical' => null,
                'robots' => null,
                'h1_count' => 0,
                'h1_text' => null,
                'has_favicon' => false,
                'has_apple_touch_icon' => false,
                'has_hsts' => false,
                'is_https' => str_starts_with(strtolower($url), 'https://'),
                'has_html_lang' => false,
                'word_count' => 0,
                'internal_links' => [],
                'error' => $errorMsg,
            ];
        }

        $status = $response->status();
        $html = $response->body();
        $headers = array_change_key_case($response->headers(), CASE_LOWER);

        $redirectTo = isset($headers['location'][0])
            ? $this->normalizeUrl((string) $headers['location'][0], $baseUrl)
            : null;

        $title = null;
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $titleMatch)) {
            $title = trim(html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $description = $this->extractMetaContent($html, 'description');
        $robots = $this->extractMetaContent($html, 'robots');

        $canonical = null;
        if (
            preg_match('/<link[^>]+rel=[\'"]canonical[\'"][^>]*href=[\'"]([^\'"]+)[\'"]/i', $html, $canonMatch)
            || preg_match('/<link[^>]+href=[\'"]([^\'"]+)[\'"][^>]*rel=[\'"]canonical[\'"]/i', $html, $canonMatch)
        ) {
            $canonical = trim($canonMatch[1]);
        }

        $h1Count = preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $h1Matches);
        $h1Text = $h1Count > 0
            ? trim(strip_tags($h1Matches[1][0]))
            : null;

        $hasFavicon = (bool) preg_match('/rel=[\'"](?:shortcut )?icon[\'"]/i', $html);
        $hasAppleTouchIcon = (bool) preg_match('/rel=[\'"]apple-touch-icon[\'"]/i', $html);
        $hasHsts = isset($headers['strict-transport-security']);
        $hasHtmlLang = (bool) preg_match('/<html[^>]*\blang=[\'"][^\'"]+[\'"]/i', $html);

        $bodyContent = $html;
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $bodyMatch)) {
            $bodyContent = $bodyMatch[1];
        }
        $cleanBody = preg_replace('/<(script|style|nav|footer)\b[^>]*>.*?<\/\1>/is', ' ', $bodyContent) ?? $bodyContent;
        $wordCount = str_word_count(trim(strip_tags($cleanBody)));

        $internalLinks = $this->extractInternalLinks($html, $url, $baseUrl);

        return [
            'url' => $url,
            'status' => $status,
            'redirect_to' => $redirectTo,
            'title' => $title !== '' ? $title : null,
            'title_length' => $title !== null ? mb_strlen($title) : 0,
            'description' => $description,
            'description_length' => $description !== null ? mb_strlen($description) : 0,
            'canonical' => $canonical !== '' ? $canonical : null,
            'robots' => $robots,
            'h1_count' => (int) $h1Count,
            'h1_text' => $h1Text,
            'has_favicon' => $hasFavicon,
            'has_apple_touch_icon' => $hasAppleTouchIcon,
            'has_hsts' => $hasHsts,
            'is_https' => str_starts_with(strtolower($url), 'https://'),
            'has_html_lang' => $hasHtmlLang,
            'word_count' => $wordCount,
            'internal_links' => $internalLinks,
            'error' => null,
        ];
    }

    protected function extractMetaContent(string $html, string $name): ?string
    {
        $quoted = preg_quote($name, '/');
        if (
            preg_match('/<meta[^>]+name=[\'"]'.$quoted.'[\'"][^>]*content=[\'"]([^\'"]*)[\'"]/i', $html, $m)
            || preg_match('/<meta[^>]+content=[\'"]([^\'"]*)[\'"][^>]*name=[\'"]'.$quoted.'[\'"]/i', $html, $m)
        ) {
            $val = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            return $val !== '' ? $val : null;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function extractInternalLinks(string $html, string $sourceUrl, string $baseUrl): array
    {
        if (! preg_match_all('/<a\s[^>]*href=[\'"]([^\'"]+)[\'"]/i', $html, $matches)) {
            return [];
        }

        $sourceHost = strtolower((string) parse_url($sourceUrl, PHP_URL_HOST));
        $baseHost = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        $links = [];

        foreach ($matches[1] as $rawHref) {
            $rawHref = trim($rawHref);
            if (
                $rawHref === ''
                || str_starts_with($rawHref, '#')
                || str_starts_with(strtolower($rawHref), 'mailto:')
                || str_starts_with(strtolower($rawHref), 'tel:')
                || str_starts_with(strtolower($rawHref), 'javascript:')
            ) {
                continue;
            }

            $normalized = $this->normalizeUrl($rawHref, $sourceUrl);
            if ($normalized === null || $normalized === $sourceUrl) {
                continue;
            }

            $linkHost = strtolower((string) parse_url($normalized, PHP_URL_HOST));
            if ($linkHost === $sourceHost || ($baseHost !== '' && $linkHost === $baseHost)) {
                $links[] = $normalized;
            }
        }

        return array_values(array_unique($links));
    }

    /**
     * @param  array<string, array<string, mixed>>  $pages
     * @return array{chains: array<int, array<string, mixed>>, loops: array<int, array<string, mixed>>}
     */
    protected function collectRedirectDiagnostics(array $pages): array
    {
        $chains = [];
        $loops = [];

        try {
            $dbRedirects = QuinrelRedirect::query()
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('is_chain', true)->orWhere('is_loop', true);
                })
                ->get();

            foreach ($dbRedirects as $redirect) {
                if ($redirect->is_loop) {
                    $loops[] = [
                        'source_url' => $redirect->source_url,
                        'target_url' => $redirect->target_url,
                        'chain' => $redirect->chain_details ?? [$redirect->target_url],
                    ];
                } elseif ($redirect->is_chain) {
                    $chains[] = [
                        'source_url' => $redirect->source_url,
                        'target_url' => $redirect->target_url,
                        'chain' => $redirect->chain_details ?? [$redirect->target_url],
                    ];
                }
            }
        } catch (Throwable) {
            // Database table may not be migrated in stateless environments
        }

        // Also inspect HTTP-level redirect hops discovered during the crawl
        $redirectGraph = [];
        foreach ($pages as $url => $page) {
            if (is_string($page['redirect_to'] ?? null) && $page['redirect_to'] !== '') {
                $redirectGraph[$url] = $page['redirect_to'];
            }
        }

        foreach ($redirectGraph as $startUrl => $firstTarget) {
            $visited = [$startUrl];
            $current = $firstTarget;
            $hops = [];
            $isLoop = false;

            while (isset($redirectGraph[$current])) {
                $hops[] = $current;
                if (in_array($current, $visited, true)) {
                    $isLoop = true;
                    break;
                }
                $visited[] = $current;
                $current = $redirectGraph[$current];
            }

            if ($isLoop) {
                $loops[] = [
                    'source_url' => $startUrl,
                    'target_url' => $firstTarget,
                    'chain' => $hops,
                ];
            } elseif ($hops !== []) {
                $hops[] = $current;
                $chains[] = [
                    'source_url' => $startUrl,
                    'target_url' => $firstTarget,
                    'chain' => $hops,
                ];
            }
        }

        return [
            'chains' => $chains,
            'loops' => $loops,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function discoverRouteUrls(string $baseUrl): array
    {
        $urls = [];
        $excludedPrefixes = [
            'quinrel',
            '_ignition',
            'sanctum',
            'livewire',
            'telescope',
            'horizon',
            'api/',
            'up',
            'storage/',
            'llms.txt',
            'llms-full.txt',
        ];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = ltrim($route->uri(), '/');
            if (str_contains($uri, '{')) {
                continue;
            }

            $skip = false;
            foreach ($excludedPrefixes as $prefix) {
                if ($uri === $prefix || str_starts_with($uri, $prefix.'/') || str_starts_with($uri, $prefix)) {
                    $skip = true;
                    break;
                }
            }

            if (! $skip) {
                $urls[] = $uri === '' ? $baseUrl : "{$baseUrl}/{$uri}";
            }
        }

        return $urls;
    }

    /**
     * @return array<int, string>
     */
    protected function discoverSitemapUrls(string $disk = 'public'): array
    {
        $urls = [];

        try {
            $storage = Storage::disk($disk);
            foreach ($storage->files() as $file) {
                if (! str_starts_with($file, 'sitemap') || ! str_ends_with($file, '.xml')) {
                    continue;
                }

                $xml = $storage->get($file);
                if (! is_string($xml) || str_contains($xml, '<sitemapindex')) {
                    continue;
                }

                if (preg_match_all('/<loc>([^<]+)<\/loc>/i', $xml, $matches)) {
                    foreach ($matches[1] as $loc) {
                        $decoded = html_entity_decode(trim($loc), ENT_XML1, 'UTF-8');
                        if ($decoded !== '') {
                            $urls[] = $decoded;
                        }
                    }
                }
            }
        } catch (Throwable) {
            // Ignore missing storage disk in non-configured environments
        }

        return $urls;
    }

    protected function normalizeUrl(string $rawUrl, string $baseUrl): ?string
    {
        $trimmed = trim($rawUrl);
        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            $trimmed = "{$scheme}:{$trimmed}";
        } elseif (str_starts_with($trimmed, '/')) {
            $origin = $this->extractOrigin($baseUrl);
            $trimmed = $origin.$trimmed;
        } elseif (! preg_match('/^https?:\/\//i', $trimmed)) {
            $origin = $this->extractOrigin($baseUrl);
            $trimmed = $origin.'/'.ltrim($trimmed, '/');
        }

        $withoutFragment = explode('#', $trimmed)[0];

        return rtrim($withoutFragment, '/') ?: $withoutFragment;
    }

    protected function extractOrigin(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return 'http://localhost';
        }

        $scheme = $parts['scheme'] ?? 'https';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return "{$scheme}://{$parts['host']}{$port}";
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildBlockedPageResult(string $url): array
    {
        return [
            'url' => $url,
            'status' => 0,
            'redirect_to' => null,
            'title' => null,
            'title_length' => 0,
            'description' => null,
            'description_length' => 0,
            'canonical' => null,
            'robots' => null,
            'h1_count' => 0,
            'h1_text' => null,
            'has_favicon' => false,
            'has_apple_touch_icon' => false,
            'has_hsts' => false,
            'is_https' => str_starts_with(strtolower($url), 'https://'),
            'has_html_lang' => false,
            'word_count' => 0,
            'internal_links' => [],
            'error' => 'Unsafe or restricted URL blocked by SSRF policy.',
        ];
    }
}
