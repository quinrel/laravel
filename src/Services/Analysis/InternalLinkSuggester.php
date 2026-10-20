<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Analysis;

use Quinrel\Laravel\Models\QuinrelMeta;

class InternalLinkSuggester
{
    /**
     * @return array<int, array{title: string, url: string}>
     */
    public function suggest(string $content, string $focusKeyword): array
    {
        if (trim($focusKeyword) === '') {
            return [];
        }

        $suggestions = QuinrelMeta::query()
            ->whereNotNull('canonical_url')
            ->where(function ($query) use ($focusKeyword) {
                $query->where('title', 'like', '%'.$focusKeyword.'%')
                    ->orWhere('description', 'like', '%'.$focusKeyword.'%')
                    ->orWhere('target_keyword', 'like', '%'.$focusKeyword.'%');
            })
            ->limit(5)
            ->get(['title', 'canonical_url']);

        $results = [];
        foreach ($suggestions as $suggestion) {
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
