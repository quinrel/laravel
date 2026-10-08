<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Analysis;

use Holvek\Laravel\Models\HolvekMeta;

class InternalLinkSuggester
{
    /**
     * @return array<int, array{title: string, url: string}>
     */
    public function suggest(string $content, string $focusKeyword): array
    {
        if (empty($focusKeyword)) {
            return [];
        }

        // Search the holvek_meta table for other pages that might be relevant
        // to the focus keyword, to suggest as internal links.
        $suggestions = HolvekMeta::query()
            ->where('title', 'like', '%'.$focusKeyword.'%')
            ->orWhere('description', 'like', '%'.$focusKeyword.'%')
            ->limit(5)
            ->get(['title', 'canonical_url']);

        $results = [];
        foreach ($suggestions as $suggestion) {
            // Only suggest if it has a canonical URL and it's not already linked in the content
            if (! empty($suggestion->canonical_url) && ! str_contains($content, $suggestion->canonical_url)) {
                $results[] = [
                    'title' => (string) $suggestion->title,
                    'url' => (string) $suggestion->canonical_url,
                ];
            }
        }

        return $results;
    }
}
