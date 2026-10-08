<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Tracking;

use Holvek\Laravel\Models\HolvekKeywordRanking;
use Holvek\Laravel\Models\HolvekMeta;
use Illuminate\Support\Collection;

class KeywordRankTracker
{
    public function __construct(
        protected SerpApiClient $serpClient
    ) {}

    public function trackAllKeywords(): void
    {
        /** @var Collection<int, HolvekMeta> $metas */
        $metas = HolvekMeta::whereNotNull('target_keyword')
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

                HolvekKeywordRanking::create([
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
            '7_days' => HolvekKeywordRanking::where('keyword', $keyword)->forDays(7)->get(),
            '30_days' => HolvekKeywordRanking::where('keyword', $keyword)->forDays(30)->get(),
            '90_days' => HolvekKeywordRanking::where('keyword', $keyword)->forDays(90)->get(),
        ];
    }
}
