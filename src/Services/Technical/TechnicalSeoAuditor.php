<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Technical;

use Quinrel\Laravel\Support\UrlValidator;

class TechnicalSeoAuditor
{
    /**
     * @return array{favicon: array{status: string, message: string}, https: array{status: string, message: string}, headers: array{status: string, message: string}, hreflang: array{status: string, message: string}}
     */
    public function auditUrl(string $url): array
    {
        if (! UrlValidator::isSafe($url)) {
            $error = ['status' => 'error', 'message' => 'Unsafe or restricted URL blocked by SSRF policy.'];

            return [
                'favicon' => $error,
                'https' => $error,
                'headers' => $error,
                'hreflang' => $error,
            ];
        }

        try {
            $response = UrlValidator::safeGet($url, 5);
            if ($response === null) {
                $error = ['status' => 'error', 'message' => 'Unsafe redirect target blocked by SSRF policy.'];

                return [
                    'favicon' => $error,
                    'https' => $error,
                    'headers' => $error,
                    'hreflang' => $error,
                ];
            }

            $html = $response->body();
            $headers = $response->headers();

            return [
                'favicon' => $this->checkFavicons($html),
                'https' => $this->checkHttps($url),
                'headers' => $this->checkSecurityHeaders($headers),
                'hreflang' => $this->checkHreflang($html),
            ];
        } catch (\Exception $e) {
            $error = ['status' => 'error', 'message' => 'Failed to reach URL: '.$e->getMessage()];

            return [
                'favicon' => $error,
                'https' => $error,
                'headers' => $error,
                'hreflang' => $error,
            ];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function checkFavicons(string $html): array
    {
        $hasFavicon = preg_match('/rel=[\'"](?:shortcut )?icon[\'"]/i', $html);
        $hasAppleTouch = preg_match('/rel=[\'"]apple-touch-icon[\'"]/i', $html);

        if (! $hasFavicon) {
            return ['status' => 'error', 'message' => 'Missing standard favicon.'];
        }
        if (! $hasAppleTouch) {
            return ['status' => 'warning', 'message' => 'Missing apple-touch-icon for iOS devices.'];
        }

        return ['status' => 'success', 'message' => 'Favicons are configured correctly.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function checkHttps(string $url): array
    {
        if (str_starts_with(strtolower($url), 'https://')) {
            return ['status' => 'success', 'message' => 'Uses HTTPS.'];
        }

        return ['status' => 'error', 'message' => 'Not using HTTPS. Security risk.'];
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array{status: string, message: string}
     */
    protected function checkSecurityHeaders(array $headers): array
    {
        // Simple heuristic: look for HSTS
        $headersLower = array_change_key_case($headers);

        if (isset($headersLower['strict-transport-security'])) {
            return ['status' => 'success', 'message' => 'HSTS header is present.'];
        }

        return ['status' => 'warning', 'message' => 'Missing Strict-Transport-Security (HSTS) header.'];
    }

    /**
     * @return array{status: string, message: string}
     */
    protected function checkHreflang(string $html): array
    {
        $hasHreflangLink = preg_match('/<link[^>]*rel=[\'"]alternate[\'"][^>]*hreflang=[\'"]([^\'"]+)[\'"]/i', $html) === 1
            || preg_match('/<link[^>]*hreflang=[\'"]([^\'"]+)[\'"][^>]*rel=[\'"]alternate[\'"]/i', $html) === 1;
        $hasHtmlLang = preg_match('/<html[^>]*lang=[\'"]([^\'"]+)[\'"]/i', $html) === 1;

        if ($hasHreflangLink && $hasHtmlLang) {
            return ['status' => 'success', 'message' => 'HTML lang and alternate hreflang attributes are configured.'];
        }

        if ($hasHtmlLang) {
            return ['status' => 'success', 'message' => 'HTML lang attribute is set.'];
        }

        return ['status' => 'warning', 'message' => 'Missing lang attribute on HTML tag and alternate hreflang links.'];
    }

    /**
     * Audit rendered HTML for technical Generative Engine Optimization (GEO) readiness.
     *
     * @return array{
     *     schema_graph: array{status: string, message: string},
     *     markdown_alternate: array{status: string, message: string},
     *     semantic_main: array{status: string, message: string}
     * }
     */
    public function auditGeoReadiness(string $html): array
    {
        $hasJsonLd = preg_match('/<script[^>]*type=[\'"]application\/ld\+json[\'"][^>]*>(.*?)<\/script>/is', $html, $schemaMatch) === 1;
        $hasRichEntity = $hasJsonLd && preg_match('/"@type"\s*:\s*"(?:FAQPage|Article|NewsArticle|TechArticle|Person|Organization|Product|HowTo)"/i', $schemaMatch[1]) === 1;

        $schemaCheck = match (true) {
            $hasRichEntity => ['status' => 'success', 'message' => 'Rich Schema.org entity JSON-LD graph detected for LLM grounding.'],
            $hasJsonLd => ['status' => 'warning', 'message' => 'JSON-LD present, but adding FAQPage, Article, or Person E-E-A-T entities improves AI grounding.'],
            default => ['status' => 'error', 'message' => 'Missing application/ld+json structured data.'],
        };

        $hasMarkdownLink = preg_match('/<link[^>]*rel=[\'"]alternate[\'"][^>]*type=[\'"]text\/markdown[\'"][^>]*>/i', $html) === 1
            || preg_match('/<link[^>]*type=[\'"]text\/markdown[\'"][^>]*rel=[\'"]alternate[\'"][^>]*>/i', $html) === 1;

        $markdownCheck = $hasMarkdownLink
            ? ['status' => 'success', 'message' => 'Markdown alternate link (<link rel="alternate" type="text/markdown">) is advertised.']
            : ['status' => 'warning', 'message' => 'Missing <link rel="alternate" type="text/markdown"> tag in <head>.'];

        $hasSemanticMain = preg_match('/<(?:main|article)\b[^>]*>/i', $html) === 1;

        $semanticCheck = $hasSemanticMain
            ? ['status' => 'success', 'message' => 'Semantic <main> or <article> container present for clean AI content extraction.']
            : ['status' => 'warning', 'message' => 'Wrap primary body copy in <main> or <article> so AI extractors can strip navigation noise.'];

        return [
            'schema_graph' => $schemaCheck,
            'markdown_alternate' => $markdownCheck,
            'semantic_main' => $semanticCheck,
        ];
    }
}
