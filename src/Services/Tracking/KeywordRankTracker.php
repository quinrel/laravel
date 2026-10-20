<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Tracking;

use Illuminate\Support\Collection;
use Quinrel\Laravel\Models\QuinrelKeywordRanking;
use Quinrel\Laravel\Models\QuinrelMeta;

class KeywordRankTracker
{
    public function __construct(
        protected SerpApiClient $serpClient
    ) {}

    public function trackAllKeywords(): void
    {
        /** @var Collection<int, QuinrelMeta> $metas */
        $metas = QuinrelMeta::whereNotNull('target_keyword')
            ->whereNotNull('canonical_url')
            ->where('target_keyword', '!=', '')
            ->get()
            ->unique('target_keyword');

        foreach ($metas as $meta) {
            /** @var string $keyword */
            $keyword = $meta->target_keyword;
            /** @var string $url */
            $url = $meta->canonical_url;

            $result = $this->serpClient->getRanking($keyword, $url);

            if (! empty($result)) {
                $rank = isset($result['rank']) ? (int) $result['rank'] : null;

                QuinrelKeywordRanking::create([
                    'keyword' => $keyword,
                    'url' => $result['url'] ?? $url,
                    'rank' => $rank,
                    'tracked_at' => now()->toDateString(),
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getHistoryCharts(string $keyword): array
    {
        return [
            '7_days' => QuinrelKeywordRanking::where('keyword', $keyword)->forDays(7)->get(),
            '30_days' => QuinrelKeywordRanking::where('keyword', $keyword)->forDays(30)->get(),
            '90_days' => QuinrelKeywordRanking::where('keyword', $keyword)->forDays(90)->get(),
        ];
    }
}
