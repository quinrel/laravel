<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AeoContentNegotiationMiddleware
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        /** @var SymfonyResponse $response */
        $response = $next($request);

        // Only convert successful HTML responses
        if (! $this->shouldConvert($request, $response)) {
            return $response;
        }

        $html = $response->getContent();
        if (empty($html)) {
            return $response;
        }

        // Try to extract the main content to avoid headers/footers if possible
        // A simple heuristic is looking for <main> or <article> tags.
        $mainContent = $this->extractMainContent($html);

        $converter = new HtmlConverter([
            'header_style' => 'atx',
            'strip_tags' => true,
        ]);

        $markdown = trim($converter->convert($mainContent));

        return response($markdown)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Vary', 'Accept')
            ->header('X-Robots-Tag', 'noindex');
    }

    protected function shouldConvert(Request $request, SymfonyResponse $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains((string) $contentType, 'text/html')) {
            return false;
        }

        // Check content negotiation
        $acceptsMarkdown = str_contains((string) $request->header('Accept', ''), 'text/markdown');
        $wantsMarkdown = $request->query('format') === 'md';

        return $acceptsMarkdown || $wantsMarkdown;
    }

    protected function extractMainContent(string $html): string
    {
        if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $html, $matches)) {
            return $matches[1];
        }

        if (preg_match('/<article[^>]*>(.*?)<\/article>/is', $html, $matches)) {
            return $matches[1];
        }

        // Fallback to body if no semantic tags
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }
}
