<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Models\QuinrelMeta;

class LlmsTxtController
{
    public function index(): Response
    {
        $ttl = max(60, (int) config('quinrel.cache.ttl', 3600));

        $content = Cache::remember('quinrel_llms_txt', $ttl, function (): string {
            $siteName = (string) config('app.name', 'Laravel');
            $description = $this->resolveDescription($siteName);

            $output = "# {$siteName}\n\n";
            $output .= "> {$description}\n\n";
            $output .= "## Navigation\n";

            $links = QuinrelMeta::query()
                ->select(['title', 'description', 'canonical_url'])
                ->whereNotNull('canonical_url')
                ->where(function ($q) {
                    $q->whereNull('robots')
                        ->orWhere('robots', 'not like', '%noindex%');
                })
                ->limit(50)
                ->cursor();

            foreach ($links as $link) {
                $title = $link->title ?: $link->canonical_url;
                $url = $link->canonical_url;
                $desc = $link->description ? " - {$link->description}" : '';
                $output .= "- [{$title}]({$url}){$desc}\n";
            }

            return $output;
        });

        return response($content)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function full(): Response
    {
        $ttl = max(60, (int) config('quinrel.cache.ttl', 3600));
        $maxLinks = max(50, (int) config('quinrel.llms_txt.max_links', 1000));

        $content = Cache::remember('quinrel_llms_full_txt', $ttl, function () use ($maxLinks): string {
            $siteName = (string) config('app.name', 'Laravel');
            $description = $this->resolveDescription($siteName);

            $output = "# {$siteName} (Full Archive)\n\n";
            $output .= "> {$description}\n\n";
            $output .= "## Markdown Content Negotiation\n";
            $output .= "Pages on this site support clean Markdown retrieval via `Accept: text/markdown` or by appending `?format=md` to any canonical URL.\n\n";
            $output .= "## All Pages\n";

            $links = QuinrelMeta::query()
                ->select(['title', 'description', 'canonical_url'])
                ->whereNotNull('canonical_url')
                ->where(function ($q) {
                    $q->whereNull('robots')
                        ->orWhere('robots', 'not like', '%noindex%');
                })
                ->limit($maxLinks)
                ->cursor();

            foreach ($links as $link) {
                $title = $link->title ?: $link->canonical_url;
                $url = $link->canonical_url;
                $desc = $link->description ? " - {$link->description}" : '';
                $output .= "- [{$title}]({$url}){$desc}\n";
            }

            return $output;
        });

        return response($content)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    protected function resolveDescription(string $siteName): string
    {
        $configured = config('quinrel.llms_txt.description') ?? config('quinrel.llm_description');

        if (is_string($configured) && trim($configured) !== '') {
            return trim($configured);
        }

        return "This is the official website for {$siteName}.";
    }
}
