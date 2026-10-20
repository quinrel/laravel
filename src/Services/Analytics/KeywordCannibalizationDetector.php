<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Analytics;

use Illuminate\Support\Collection;
use Quinrel\Laravel\Models\QuinrelMeta;

class KeywordCannibalizationDetector
{
    /**
     * Detect pages competing for the same target keyword using SQL aggregation before hydrating models.
     *
     * @return Collection<string, Collection<int, QuinrelMeta>>
     */
    public function detect(): Collection
    {
        /** @var array<int, string> $duplicateKeywords */
        $duplicateKeywords = QuinrelMeta::query()
            ->selectRaw('LOWER(TRIM(target_keyword)) as normalized_keyword')
            ->whereNotNull('target_keyword')
            ->where('target_keyword', '!=', '')
            ->groupByRaw('LOWER(TRIM(target_keyword))')
            ->havingRaw('COUNT(*) > 1')
            ->toBase()
            ->pluck('normalized_keyword')
            ->all();

        if ($duplicateKeywords === []) {
            /** @var Collection<string, Collection<int, QuinrelMeta>> $empty */
            $empty = collect();

            return $empty;
        }

        $placeholders = implode(',', array_fill(0, count($duplicateKeywords), '?'));

        /** @var Collection<int, QuinrelMeta> $metas */
        $metas = QuinrelMeta::query()
            ->whereNotNull('target_keyword')
            ->whereRaw("LOWER(TRIM(target_keyword)) IN ({$placeholders})", $duplicateKeywords)
            ->get();

        return $metas->groupBy(function (QuinrelMeta $meta) {
            return mb_strtolower(trim((string) $meta->target_keyword));
        })->filter(function (Collection $group) {
            return $group->count() > 1;
        });
    }
}
