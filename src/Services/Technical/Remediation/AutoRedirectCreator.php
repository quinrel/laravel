<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Technical\Remediation;

use Illuminate\Support\Facades\Route;
use Quinrel\Laravel\Models\Quinrel404Log;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Models\QuinrelRedirect;

class AutoRedirectCreator
{
    /**
     * Find the closest matching valid URL for a broken 404 URL using Levenshtein and similar_text slug matching.
     *
     * @param  array<int, string>  $candidateUrls
     * @return array{target_url: string, confidence: float}|null
     */
    public function suggestTarget(string $brokenUrl, array $candidateUrls = [], float $minConfidence = 55.0): ?array
    {
        $brokenPath = $this->extractPath($brokenUrl);
        $brokenSlug = $this->extractLastSegment($brokenPath);

        if ($brokenSlug === '') {
            return null;
        }

        if ($candidateUrls === []) {
            $candidateUrls = $this->collectValidCandidateUrls();
        }

        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($candidateUrls as $candidate) {
            $candidatePath = $this->extractPath($candidate);
            if ($candidatePath === $brokenPath || $candidatePath === '' || $candidatePath === '/') {
                continue;
            }

            $candidateSlug = $this->extractLastSegment($candidatePath);
            if ($candidateSlug === '') {
                continue;
            }

            similar_text($brokenSlug, $candidateSlug, $slugPercent);
            similar_text($brokenPath, $candidatePath, $pathPercent);

            $maxLen = max(strlen($brokenSlug), strlen($candidateSlug), 1);
            $levDistance = levenshtein(substr($brokenSlug, 0, 255), substr($candidateSlug, 0, 255));
            $levScore = max(0.0, (1.0 - ($levDistance / $maxLen)) * 100.0);

            $compositeScore = ($slugPercent * 0.5) + ($levScore * 0.35) + ($pathPercent * 0.15);

            if ($compositeScore > $bestScore) {
                $bestScore = $compositeScore;
                $bestMatch = $candidate;
            }
        }

        if ($bestMatch === null || $bestScore < $minConfidence) {
            return null;
        }

        return [
            'target_url' => $bestMatch,
            'confidence' => round($bestScore, 2),
        ];
    }

    /**
     * Create an idempotent 301 redirect from a 404 log or broken URL to its suggested or explicit target URL.
     *
     * @param  array<int, string>  $candidateUrls
     */
    public function createFrom404(
        Quinrel404Log|string $brokenSource,
        ?string $targetUrl = null,
        int $statusCode = 301,
        array $candidateUrls = []
    ): ?QuinrelRedirect {
        $rawUrl = $brokenSource instanceof Quinrel404Log ? $brokenSource->url : $brokenSource;
        $sourcePath = $this->extractPath($rawUrl);

        if ($sourcePath === '') {
            return null;
        }

        if ($targetUrl === null || trim($targetUrl) === '') {
            $suggestion = $this->suggestTarget($rawUrl, $candidateUrls);
            if ($suggestion === null) {
                return null;
            }
            $targetUrl = $suggestion['target_url'];
        }

        $targetPath = $this->normalizeTarget($targetUrl, $rawUrl);
        if ($targetPath === $sourcePath || $targetPath === $rawUrl) {
            return null;
        }

        // Prevent creating a direct 2-hop loop
        $reverseExists = QuinrelRedirect::query()
            ->where('source_url', $targetPath)
            ->where('target_url', $sourcePath)
            ->where('is_active', true)
            ->exists();

        if ($reverseExists) {
            return null;
        }

        /** @var QuinrelRedirect $redirect */
        $redirect = QuinrelRedirect::updateOrCreate(
            ['source_url' => $sourcePath],
            [
                'target_url' => $targetPath,
                'status_code' => $statusCode,
                'is_regex' => false,
                'is_active' => true,
            ]
        );

        if ($brokenSource instanceof Quinrel404Log && $brokenSource->exists) {
            $brokenSource->delete();
        } else {
            Quinrel404Log::where('url', $rawUrl)->orWhere('url', $sourcePath)->delete();
        }

        return $redirect;
    }

    /**
     * @return array<int, string>
     */
    protected function collectValidCandidateUrls(): array
    {
        $candidates = [];

        foreach (QuinrelMeta::query()->whereNotNull('canonical_url')->pluck('canonical_url') as $canonical) {
            if (is_string($canonical) && $canonical !== '') {
                $candidates[] = $this->extractPath($canonical);
            }
        }

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uri = '/'.ltrim($route->uri(), '/');
            if (! str_contains($uri, '{') && ! str_starts_with($uri, '/quinrel') && ! str_starts_with($uri, '/_ignition')) {
                $candidates[] = $uri;
            }
        }

        return array_values(array_unique($candidates));
    }

    protected function extractPath(string $url): string
    {
        $path = (string) (parse_url(trim($url), PHP_URL_PATH) ?: '');
        if ($path === '') {
            return '/';
        }

        return '/'.ltrim(rtrim($path, '/'), '/');
    }

    protected function extractLastSegment(string $path): string
    {
        $trimmed = trim($path, '/');
        if ($trimmed === '') {
            return '';
        }

        $segments = explode('/', $trimmed);

        return strtolower((string) end($segments));
    }

    protected function normalizeTarget(string $targetUrl, string $sourceUrl): string
    {
        $trimmed = trim($targetUrl);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            if (str_starts_with($sourceUrl, '/')) {
                return $this->extractPath($trimmed);
            }

            return $trimmed;
        }

        return $this->extractPath($trimmed);
    }
}
