<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services;

use Illuminate\Support\Facades\File;

class RobotsTxtManager
{
    /**
     * AI Search & Citation user-agents that power live retrieval and citations in AI answers.
     *
     * @var array<int, string>
     */
    public const AI_SEARCH_BOTS = [
        'OAI-SearchBot',
        'ChatGPT-User',
        'PerplexityBot',
        'Claude-SearchBot',
        'Claude-User',
        'Applebot',
    ];

    /**
     * AI Model Training scrapers used for foundational corpus training.
     *
     * @var array<int, string>
     */
    public const AI_TRAINING_BOTS = [
        'GPTBot',
        'ClaudeBot',
        'anthropic-ai',
        'Google-Extended',
        'CCBot',
        'Bytespider',
    ];

    protected string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? public_path('robots.txt');
    }

    public function get(): string
    {
        if (File::exists($this->path)) {
            return File::get($this->path);
        }

        return "User-agent: *\nDisallow:";
    }

    public function save(string $content): bool
    {
        return File::put($this->path, $content) !== false;
    }

    /**
     * Identify a Disallow directive in robots.txt that blocks the given URL.
     */
    public function findBlockingRule(string $url): ?string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');

        $lines = preg_split('/\r\n|\r|\n/', $this->get()) ?: [];
        foreach ($lines as $line) {
            $trimmed = trim((string) preg_replace('/#.*$/', '', $line));
            if (! str_starts_with(strtolower($trimmed), 'disallow:')) {
                continue;
            }

            $pattern = trim(substr($trimmed, strlen('disallow:')));
            if ($pattern === '') {
                continue;
            }

            if ($this->matchesRobotsPattern($path, $pattern)) {
                return "Disallow: {$pattern}";
            }
        }

        return null;
    }

    protected function matchesRobotsPattern(string $path, string $pattern): bool
    {
        if ($pattern === '/') {
            return true;
        }

        $anchoredEnd = str_ends_with($pattern, '$');
        $cleanPattern = $anchoredEnd ? substr($pattern, 0, -1) : $pattern;
        $regex = '#^'.str_replace('\*', '.*', preg_quote($cleanPattern, '#')).($anchoredEnd ? '$' : '').'#';

        return (bool) preg_match($regex, $path);
    }

    /**
     * Determine whether a specific bot User-Agent is allowed to crawl the given path per RFC 9309.
     */
    public function isBotAllowed(string $userAgent, string $path = '/'): bool
    {
        $groups = $this->parseGroups($this->get());
        $uaLower = strtolower(trim($userAgent));

        $targetRules = $groups[$uaLower] ?? ($groups['*'] ?? []);
        if (empty($targetRules)) {
            return true;
        }

        $bestMatchLength = -1;
        $allowed = true;

        foreach ($targetRules as $rule) {
            $pattern = $rule['pattern'];
            if ($pattern === '') {
                continue;
            }

            if ($this->matchesRobotsPattern($path, $pattern)) {
                $length = strlen($pattern);
                if ($length > $bestMatchLength) {
                    $bestMatchLength = $length;
                    $allowed = $rule['type'] === 'allow';
                } elseif ($length === $bestMatchLength && $rule['type'] === 'allow') {
                    // RFC 9309: Allow wins over Disallow when pattern lengths are equal
                    $allowed = true;
                }
            }
        }

        return $allowed;
    }

    /**
     * Audit robots.txt for AI Search/Citation bots vs AI Training scrapers and Sitemap presence.
     *
     * @return array{
     *     search_bots: array<string, bool>,
     *     training_bots: array<string, bool>,
     *     all_search_bots_allowed: bool,
     *     training_bots_blocked: bool,
     *     sitemap_referenced: bool
     * }
     */
    public function auditAiBots(string $path = '/'): array
    {
        $searchBots = [];
        foreach (self::AI_SEARCH_BOTS as $bot) {
            $searchBots[$bot] = $this->isBotAllowed($bot, $path);
        }

        $trainingBots = [];
        foreach (self::AI_TRAINING_BOTS as $bot) {
            $trainingBots[$bot] = $this->isBotAllowed($bot, $path);
        }

        $allSearchAllowed = ! in_array(false, $searchBots, true);
        $allTrainingBlocked = ! in_array(true, $trainingBots, true);
        $sitemapReferenced = preg_match('/^\s*Sitemap:\s*https?:\/\/\S+/im', $this->get()) === 1;

        return [
            'search_bots' => $searchBots,
            'training_bots' => $trainingBots,
            'all_search_bots_allowed' => $allSearchAllowed,
            'training_bots_blocked' => $allTrainingBlocked,
            'sitemap_referenced' => $sitemapReferenced,
        ];
    }

    /**
     * Generate a managed GEO robots.txt block controlling AI Search bots and AI Training bots.
     */
    public function generateGeoRules(
        bool $allowSearchBots = true,
        bool $blockTrainingBots = false,
        ?string $sitemapUrl = null
    ): string {
        $lines = ['# BEGIN Quinrel GEO'];

        $searchDirective = $allowSearchBots ? 'Allow: /' : 'Disallow: /';
        foreach (self::AI_SEARCH_BOTS as $bot) {
            $lines[] = "User-agent: {$bot}";
            $lines[] = $searchDirective;
            $lines[] = '';
        }

        $trainingDirective = $blockTrainingBots ? 'Disallow: /' : 'Allow: /';
        foreach (self::AI_TRAINING_BOTS as $bot) {
            $lines[] = "User-agent: {$bot}";
            $lines[] = $trainingDirective;
            $lines[] = '';
        }

        $resolvedSitemap = $sitemapUrl ?? rtrim((string) config('app.url', 'http://localhost'), '/').'/sitemap.xml';
        if ($resolvedSitemap !== '') {
            $lines[] = "Sitemap: {$resolvedSitemap}";
        }

        $lines[] = '# END Quinrel GEO';

        return implode("\n", $lines);
    }

    /**
     * Idempotently apply or update the managed Quinrel GEO block in robots.txt without overwriting custom host rules.
     */
    public function applyGeoPolicy(
        bool $allowSearchBots = true,
        bool $blockTrainingBots = false,
        ?string $sitemapUrl = null
    ): bool {
        $current = $this->get();
        $geoBlock = $this->generateGeoRules($allowSearchBots, $blockTrainingBots, $sitemapUrl);

        $pattern = '/# BEGIN Quinrel GEO.*?# END Quinrel GEO/s';
        if (preg_match($pattern, $current) === 1) {
            $updated = (string) preg_replace($pattern, $geoBlock, $current);
        } else {
            $updated = rtrim($current)."\n\n".$geoBlock."\n";
        }

        return $this->save($updated);
    }

    /**
     * @return array<string, array<int, array{type: 'allow'|'disallow', pattern: string}>>
     */
    protected function parseGroups(string $content): array
    {
        $groups = [];
        $currentAgents = [];
        $seenRuleInGroup = false;

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        foreach ($lines as $line) {
            $trimmed = trim((string) preg_replace('/#.*$/', '', $line));
            if ($trimmed === '') {
                continue;
            }

            if (! str_contains($trimmed, ':')) {
                continue;
            }

            [$field, $value] = explode(':', $trimmed, 2);
            $fieldLower = strtolower(trim($field));
            $value = trim($value);

            if ($fieldLower === 'user-agent') {
                if ($seenRuleInGroup) {
                    $currentAgents = [];
                    $seenRuleInGroup = false;
                }
                $agentKey = strtolower($value);
                $currentAgents[] = $agentKey;
                $groups[$agentKey] ??= [];

                continue;
            }

            if (in_array($fieldLower, ['allow', 'disallow'], true) && ! empty($currentAgents)) {
                $seenRuleInGroup = true;
                /** @var 'allow'|'disallow' $ruleType */
                $ruleType = $fieldLower;
                foreach ($currentAgents as $agentKey) {
                    $groups[$agentKey][] = [
                        'type' => $ruleType,
                        'pattern' => $value,
                    ];
                }
            }
        }

        return $groups;
    }
}
